<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Form;
use App\Entity\FormField;
use App\Entity\FormFieldTranslation;
use App\Enum\FormFieldTypeEnum;
use App\Service\FormSubmissionValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class FormSubmissionValidatorTest extends TestCase
{
    private FormSubmissionValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new FormSubmissionValidator(Validation::createValidator());
    }

    private function makeField(string $label, FormFieldTypeEnum $type = FormFieldTypeEnum::Text, bool $required = false): FormField
    {
        $field = new FormField();
        $field->setType($type);
        $field->setRequired($required);
        $field->setPosition(0);

        $translation = new FormFieldTranslation();
        $translation->setLocale('fr');
        $translation->setLabel($label);
        $field->addTranslation($translation);

        return $field;
    }

    private function makeForm(FormField ...$fields): Form
    {
        $form = new Form();
        $form->setActive(true);
        foreach ($fields as $field) {
            $field->setForm($form);
            $form->addField($field);
        }

        return $form;
    }

    public function testNoErrorsWhenAllRequiredFieldsFilled(): void
    {
        $field = $this->makeField('Nom', FormFieldTypeEnum::Text, required: true);
        $form = $this->makeForm($field);

        $errors = $this->validator->validate($form, [(string) $field->getId() => 'Jean']);

        self::assertSame([], $errors);
    }

    public function testErrorWhenRequiredFieldEmpty(): void
    {
        $field = $this->makeField('Nom', FormFieldTypeEnum::Text, required: true);
        $form = $this->makeForm($field);

        $errors = $this->validator->validate($form, []);

        self::assertArrayHasKey((string) $field->getId(), $errors);
    }

    public function testNoErrorWhenOptionalFieldEmpty(): void
    {
        $field = $this->makeField('Commentaire', FormFieldTypeEnum::Textarea, required: false);
        $form = $this->makeForm($field);

        $errors = $this->validator->validate($form, []);

        self::assertSame([], $errors);
    }

    public function testEmailFieldValidation(): void
    {
        $field = $this->makeField('Email', FormFieldTypeEnum::Email, required: false);
        $form = $this->makeForm($field);

        $errorsInvalid = $this->validator->validate($form, [(string) $field->getId() => 'not-an-email']);
        self::assertArrayHasKey((string) $field->getId(), $errorsInvalid);

        $errorsValid = $this->validator->validate($form, [(string) $field->getId() => 'test@example.com']);
        self::assertArrayNotHasKey((string) $field->getId(), $errorsValid);
    }

    public function testExtractSubmittedDataNormalizesValues(): void
    {
        $textField = $this->makeField('Nom', FormFieldTypeEnum::Text, required: true);
        $form = $this->makeForm($textField);

        $data = $this->validator->extractSubmittedData($form, [(string) $textField->getId() => 'Jean']);

        self::assertSame(['Jean'], array_values($data));
    }

    public function testExtractSubmittedDataHandlesArrayValues(): void
    {
        $checkboxField = $this->makeField('Options', FormFieldTypeEnum::Checkbox, required: false);
        $form = $this->makeForm($checkboxField);

        $data = $this->validator->extractSubmittedData($form, [(string) $checkboxField->getId() => ['a', 'b']]);

        self::assertSame(['a', 'b'], array_values($data)[(string) $checkboxField->getId() === array_key_first($data) ? 0 : 0]);
    }

    public function testExtractSubmittedDataSkipsNullValues(): void
    {
        $field = $this->makeField('Nom', FormFieldTypeEnum::Text, required: false);
        $form = $this->makeForm($field);

        $data = $this->validator->extractSubmittedData($form, []);

        self::assertSame([], $data);
    }
}

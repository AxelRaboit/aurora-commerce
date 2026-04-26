<?php

declare(strict_types=1);

namespace App\Contract\Form;

use App\DTO\Form\FormFieldInput;
use App\DTO\Form\FormInput;
use App\Entity\Form;
use App\Entity\FormField;
use App\Entity\FormSubmission;

interface FormManagerInterface
{
    public function create(FormInput $input): Form;

    public function update(Form $form, FormInput $input): void;

    public function delete(Form $form): void;

    public function createField(Form $form, FormFieldInput $input): FormField;

    public function updateField(FormField $field, FormFieldInput $input): void;

    public function deleteField(FormField $field): void;

    /** @param int[] $orderedIds */
    public function reorderFields(Form $form, array $orderedIds): void;

    /** @param array<string, mixed> $submittedData */
    public function submit(Form $form, array $submittedData, string $locale, string $ip): FormSubmission;
}

<?php

declare(strict_types=1);

namespace Aurora\Core\User\Dto;

use Aurora\Core\Support\Str;
use Aurora\Core\User\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class MoodInput
{
    public function __construct(
        #[Assert\Length(max: User::MOOD_MESSAGE_MAX_LENGTH, maxMessage: 'backend.profile.mood.errors.too_long')]
        public ?string $moodMessage = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $data = json_decode($request->getContent(), true);
        $data = is_array($data) ? $data : [];

        $raw = Str::trimOrNullFromArray($data, 'moodMessage');

        return new self(moodMessage: '' === $raw ? null : $raw);
    }
}

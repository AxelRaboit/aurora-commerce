<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Recurring\Message;

/**
 * Tick fired by the main schedule once a day. Marker class — the
 * handler ({@see GeneratePersonalFinanceRecurringTransactionsHandler})
 * iterates over due rules and materialises them.
 */
final readonly class GeneratePersonalFinanceRecurringTransactionsMessage {}

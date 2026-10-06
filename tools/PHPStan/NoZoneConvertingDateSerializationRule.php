<?php

declare(strict_types=1);

namespace Tools\PHPStan;

use DateTimeInterface;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

/**
 * Forbid serializing a datetime in a way that converts or names its timezone.
 *
 * Every datetime this app handles is a zone-less wall clock in
 * `config('app.timezone')`: the database stores no offset, the API sends none
 * (`SerializesWallClockDates`), and the frontend reads them all as such
 * (`resources/js/appTime.ts`). A serializer that converts to UTC or writes an
 * offset therefore moves the value.
 *
 * Use `->toDateTimeString()`, or `->format('Y-m-d H:i:s')`. If a value genuinely
 * has to leave wall-clock space - an iCal feed, an HTTP date header - silence
 * this with a phpstan-ignore-line annotation and write down why in a comment.
 *
 * @implements Rule<MethodCall>
 */
final class NoZoneConvertingDateSerializationRule implements Rule
{
    /**
     * Carbon and DateTime methods that convert the zone or write it into the
     * output, compared lowercased because PHP method names are case-insensitive.
     *
     * @var list<string>
     */
    private const array BANNED_METHODS = [
        'toisostring',
        'toiso8601string',
        'toiso8601zulustring',
        'tojson',
        'toatomstring',
        'tow3cstring',
        'torfc3339string',
        'torfc3339extendedstring',
        'torfc822string',
        'torfc850string',
        'torfc1036string',
        'torfc1123string',
        'torfc2822string',
        'torssstring',
        'tocookiestring',
        'utc',
        'settimezone',
        'settimezonefrom',
        'shifttimezone',
        'tz',
        'timezone',
    ];

    /**
     * `format()` characters that emit an offset or a zone name, plus `c` and `r`
     * whose whole expansion carries one. Without this, banning the methods above
     * would only mean the next mistake is spelled `format(DATE_ATOM)`.
     */
    private const string ZONE_PLACEHOLDERS = 'ceOPpTZr';

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node->name instanceof Node\Identifier) {
            return [];
        }

        $dateTime = new ObjectType(DateTimeInterface::class);

        if (! $dateTime->isSuperTypeOf($scope->getType($node->var))->yes()) {
            return [];
        }

        $method = strtolower($node->name->toString());

        if (in_array($method, self::BANNED_METHODS, true)) {
            return [$this->error(sprintf(
                '%s() converts or labels the timezone of a datetime that is stored as a zone-less wall clock in config(\'app.timezone\'). Use toDateTimeString().',
                $node->name->toString(),
            ))];
        }

        if ($method === 'format' && $this->formatCarriesZone($node, $scope)) {
            return [$this->error(
                'This format() writes a timezone offset or name onto a datetime that is stored as a zone-less wall clock in config(\'app.timezone\'). Use the format \'Y-m-d H:i:s\'.',
            )];
        }

        return [];
    }

    private function formatCarriesZone(MethodCall $node, Scope $scope): bool
    {
        $args = $node->getArgs();

        if ($args === []) {
            return false;
        }

        $formats = $scope->getType($args[0]->value)->getConstantStrings();

        if ($formats === []) {
            // A format built at runtime cannot be read here. Every caller in this
            // app passes a literal, so stay quiet rather than guess at one.
            return false;
        }

        foreach ($formats as $format) {
            if ($this->hasZonePlaceholder($format->getValue())) {
                return true;
            }
        }

        return false;
    }

    private function hasZonePlaceholder(string $format): bool
    {
        $length = strlen($format);

        for ($i = 0; $i < $length; $i++) {
            if ($format[$i] === '\\') {
                // An escaped character is literal text, so `Y-m-d\TH:i:s` is fine.
                $i++;

                continue;
            }

            if (str_contains(self::ZONE_PLACEHOLDERS, $format[$i])) {
                return true;
            }
        }

        return false;
    }

    private function error(string $message): IdentifierRuleError
    {
        return RuleErrorBuilder::message($message)
            ->identifier('roomz.zoneConvertingDatetime')
            ->build();
    }
}

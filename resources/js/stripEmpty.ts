/**
 * Removes keys whose translation is an empty string.
 *
 * A key that carries `""` is a key someone has not translated yet, but
 * laravel-vue-i18n registers it as present and renders the blank. Dropping
 * those keys before registration makes them indistinguishable from truly
 * absent ones, so the fallback locale supplies the text instead. See #248.
 *
 * The input is always a flat map of strings: laravel-vue-i18n's Vite plugin
 * converts Laravel's nested groups to dotted keys and discards every
 * non-string leaf when it generates `lang/php_*.json`, so nothing else can
 * reach this function.
 */
export function stripEmpty(messages: Record<string, string>): Record<string, string> {
    return Object.fromEntries(Object.entries(messages).filter(([, value]) => value !== ""));
}

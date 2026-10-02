export function assertDomTypesAreUnavailable(): void {
    // @ts-expect-error This package deliberately excludes the DOM library.
    void document;
}

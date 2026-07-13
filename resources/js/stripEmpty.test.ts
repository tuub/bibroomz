import { stripEmpty } from "@/stripEmpty";

import { describe, expect, test } from "vitest";

describe("stripEmpty", () => {
    test("returns non-object values unchanged", () => {
        expect(stripEmpty("hello")).toBe("hello");
        expect(stripEmpty(42)).toBe(42);
        expect(stripEmpty(null)).toBe(null);
        expect(stripEmpty(undefined)).toBe(undefined);
    });

    test("drops keys whose value is an empty string", () => {
        expect(stripEmpty({ a: "", b: "keep" })).toEqual({ b: "keep" });
    });

    test("keeps falsy non-string values", () => {
        expect(stripEmpty({ a: 0, b: false, c: null })).toEqual({ a: 0, b: false, c: null });
    });

    test("recursively strips empty strings from nested objects", () => {
        expect(stripEmpty({ a: { b: "", c: "keep" } })).toEqual({ a: { c: "keep" } });
    });

    test("drops nested objects that become empty after stripping", () => {
        expect(stripEmpty({ a: { b: "" }, c: "keep" })).toEqual({ c: "keep" });
    });

    test("handles arrays as objects, stripping empty-string elements by index", () => {
        expect(stripEmpty({ a: ["", "keep"] })).toEqual({ a: { 1: "keep" } });
    });

    test("handles deeply nested structures", () => {
        expect(stripEmpty({ a: { b: { c: "", d: "keep" }, e: "" } })).toEqual({ a: { b: { d: "keep" } } });
    });

    test("returns an empty object when everything is stripped", () => {
        expect(stripEmpty({ a: "", b: { c: "" } })).toEqual({});
    });
});

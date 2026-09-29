import { stripEmpty } from "@/stripEmpty";

import { describe, expect, test } from "vitest";

describe("stripEmpty", () => {
    test("drops keys whose value is an empty string", () => {
        expect(stripEmpty({ a: "", b: "keep" })).toEqual({ b: "keep" });
    });

    test("keeps keys whose value is only whitespace", () => {
        expect(stripEmpty({ a: " " })).toEqual({ a: " " });
    });

    test("keeps dotted keys as the plugin generates them", () => {
        expect(stripEmpty({ "group.inner": "keep", "group.blank": "" })).toEqual({ "group.inner": "keep" });
    });

    test("returns an empty object when everything is stripped", () => {
        expect(stripEmpty({ a: "", b: "" })).toEqual({});
    });

    test("returns an empty object unchanged", () => {
        expect(stripEmpty({})).toEqual({});
    });

    test("does not mutate the input", () => {
        const messages = { a: "", b: "keep" };

        stripEmpty(messages);

        expect(messages).toEqual({ a: "", b: "keep" });
    });
});

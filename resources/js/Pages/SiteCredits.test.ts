import SiteCredits from "@/Pages/SiteCredits.vue";

import { mount } from "@vue/test-utils";
import { describe, expect, test } from "vitest";

const NOTICES_URL = "https://example.org/build/third-party-notices.txt";

function render() {
    return mount(SiteCredits, {
        props: {
            noticesUrl: NOTICES_URL,
        },
        global: {
            mocks: {
                $t: (key: string) => key,
            },
            stubs: {
                Breadcrumbs: true,
            },
        },
    });
}

describe("SiteCredits", () => {
    test("names every work this site ships under an attribution license", () => {
        const wrapper = render();
        const attributions = wrapper.findAll("li");

        expect(attributions).toHaveLength(3);
        expect(attributions.map((attribution) => attribution.get("a").attributes("href"))).toEqual([
            "site_credits.paragraphs.third_party.noto_sans.link",
            "site_credits.paragraphs.third_party.remix_icon.link",
            "site_credits.paragraphs.third_party.prime_icons.link",
        ]);
    });

    test("hands the reader the license texts the bundle itself does not carry", () => {
        const wrapper = render();
        const notices = wrapper.get(`a[href="${NOTICES_URL}"]`);

        expect(notices.text()).toContain("notices.link_label");
    });

    test("links the licenses themselves, not just their names", () => {
        const wrapper = render();

        for (const link of wrapper.findAll("li a")) {
            expect(link.attributes("target")).toBe("_blank");
            expect(link.attributes("rel")).toBe("noopener noreferrer");
            expect(link.text()).toContain("link_label");
        }
    });
});

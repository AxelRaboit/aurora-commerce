import { describe, it, expect } from "vitest";
import { mount } from "@vue/test-utils";
import AppAvatar from "./AppAvatar.vue";

describe("AppAvatar", () => {
    it("renders initials from name prop", () => {
        const wrapper = mount(AppAvatar, { props: { name: "John Doe" } });
        expect(wrapper.find("span").text()).toBe("JD");
    });

    it("renders initials from firstName + lastName when no name given", () => {
        const wrapper = mount(AppAvatar, {
            props: { firstName: "Alice", lastName: "Martin" },
        });
        expect(wrapper.find("span").text()).toBe("AM");
    });

    it("renders an img when photoUrl is provided", () => {
        const wrapper = mount(AppAvatar, {
            props: { name: "John", photoUrl: "https://example.com/avatar.jpg" },
        });
        expect(wrapper.find("img").exists()).toBe(true);
        expect(wrapper.find("img").attributes("src")).toBe(
            "https://example.com/avatar.jpg",
        );
        expect(wrapper.find("span").exists()).toBe(false);
    });

    it("applies solid variant class when variant=solid", () => {
        const wrapper = mount(AppAvatar, {
            props: { name: "Bob", variant: "solid" },
        });
        expect(wrapper.find("div").classes()).toContain("bg-accent-600");
    });

    it("applies custom pixel size via inline style when size is a number", () => {
        const wrapper = mount(AppAvatar, { props: { name: "Test", size: 48 } });
        const style = wrapper.find("div").attributes("style");
        expect(style).toContain("width: 48px");
        expect(style).toContain("height: 48px");
    });
});

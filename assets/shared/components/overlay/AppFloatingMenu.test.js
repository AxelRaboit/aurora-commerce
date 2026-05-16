import { describe, it, expect } from "vitest";
import { mount } from "@vue/test-utils";
import AppFloatingMenu from "./AppFloatingMenu.vue";

const items = [
    { id: "a", label: "Alpha" },
    { id: "b", label: "Bravo" },
    { id: "c", label: "Charlie" },
];

function renderMenu(overrides = {}) {
    return mount(AppFloatingMenu, {
        props: {
            items,
            position: { top: 10, left: 20 },
            activeIndex: 0,
            ...overrides,
        },
        slots: {
            default: `<template #default="{ item }">{{ item.label }}</template>`,
        },
    });
}

describe("AppFloatingMenu", () => {
    it("renders one button per item", () => {
        const wrapper = renderMenu();
        expect(wrapper.findAll("button")).toHaveLength(3);
    });

    it("forwards the scoped slot for each item's content", () => {
        const wrapper = renderMenu();
        const text = wrapper.findAll("button").map((b) => b.text());
        expect(text).toEqual(["Alpha", "Bravo", "Charlie"]);
    });

    it("applies the active class to the row matching activeIndex", () => {
        const wrapper = renderMenu({ activeIndex: 1 });
        const buttons = wrapper.findAll("button");
        expect(buttons[0].classes()).not.toContain("bg-accent-500/15");
        expect(buttons[1].classes()).toContain("bg-accent-500/15");
        expect(buttons[2].classes()).not.toContain("bg-accent-500/15");
    });

    it("positions the menu via inline top/left from the position prop", () => {
        const wrapper = renderMenu({ position: { top: 42, left: 99 } });
        const style = wrapper.find('[class*="absolute"]').attributes("style");
        expect(style).toContain("top: 42px");
        expect(style).toContain("left: 99px");
    });

    it("emits select(item) when a row is clicked", () => {
        const wrapper = renderMenu();
        wrapper.findAll("button")[1].trigger("mousedown");
        expect(wrapper.emitted("select")?.[0]).toEqual([items[1]]);
    });

    it("emits highlight(index) on mouseenter", () => {
        const wrapper = renderMenu();
        wrapper.findAll("button")[2].trigger("mouseenter");
        expect(wrapper.emitted("highlight")?.[0]).toEqual([2]);
    });

    it("honors a custom min-width class", () => {
        const wrapper = renderMenu({ minWidthClass: "min-w-96" });
        expect(wrapper.find('[class*="absolute"]').classes()).toContain(
            "min-w-96",
        );
    });

    it("renders the header slot above the list when provided", () => {
        const wrapper = mount(AppFloatingMenu, {
            props: {
                items,
                position: { top: 0, left: 0 },
                activeIndex: 0,
            },
            slots: {
                header: `<div class="my-header">Search: foo</div>`,
                default: `<template #default="{ item }">{{ item.label }}</template>`,
            },
        });
        const header = wrapper.find(".my-header");
        expect(header.exists()).toBe(true);
        expect(header.text()).toBe("Search: foo");
    });

    it("does not render a header wrapper when the slot is not provided", () => {
        const wrapper = renderMenu();
        // No header div with border-b class should exist
        expect(wrapper.find(".border-b").exists()).toBe(false);
    });
});

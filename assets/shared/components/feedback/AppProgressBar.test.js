import { describe, it, expect } from "vitest";
import { mount } from "@vue/test-utils";
import AppProgressBar from "./AppProgressBar.vue";

describe("AppProgressBar", () => {
    // The template structure is: div.space-y-1 > div.w-full.bg-line... > div (the fill bar)
    // wrapper.findAll("div")[2] is the fill bar (innermost div with the style)
    const getTrack = (wrapper) => wrapper.findAll("div")[1];
    const getFillBar = (wrapper) => wrapper.findAll("div")[2];

    it("sets the bar width to the given value", () => {
        const wrapper = mount(AppProgressBar, { props: { value: 60 } });
        expect(getFillBar(wrapper).attributes("style")).toContain("width: 60%");
    });

    it("clamps value above 100 to 100%", () => {
        const wrapper = mount(AppProgressBar, { props: { value: 150 } });
        expect(getFillBar(wrapper).attributes("style")).toContain(
            "width: 100%",
        );
    });

    it("clamps negative value to 0%", () => {
        const wrapper = mount(AppProgressBar, { props: { value: -10 } });
        expect(getFillBar(wrapper).attributes("style")).toContain("width: 0%");
    });

    it("hides the label by default", () => {
        const wrapper = mount(AppProgressBar, { props: { value: 50 } });
        expect(wrapper.find("p").exists()).toBe(false);
    });

    it("shows the default percentage label when showLabel is true", () => {
        const wrapper = mount(AppProgressBar, {
            props: { value: 42, showLabel: true },
        });
        expect(wrapper.find("p").exists()).toBe(true);
        expect(wrapper.find("p").text()).toBe("42%");
    });

    it("shows a custom label when provided", () => {
        const wrapper = mount(AppProgressBar, {
            props: { value: 75, showLabel: true, label: "3 of 4 done" },
        });
        expect(wrapper.find("p").text()).toBe("3 of 4 done");
    });

    it("applies the emerald color class to the fill bar", () => {
        const wrapper = mount(AppProgressBar, {
            props: { value: 50, color: "emerald" },
        });
        expect(getFillBar(wrapper).classes()).toContain("bg-emerald-500");
    });

    it("applies md size class to the track by default", () => {
        const wrapper = mount(AppProgressBar, { props: { value: 50 } });
        expect(getTrack(wrapper).classes()).toContain("h-2");
    });

    it("applies sm size class to the track", () => {
        const wrapper = mount(AppProgressBar, {
            props: { value: 50, size: "sm" },
        });
        expect(getTrack(wrapper).classes()).toContain("h-1.5");
    });
});

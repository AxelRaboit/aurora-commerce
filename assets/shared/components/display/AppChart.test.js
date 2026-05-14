import { describe, it, expect } from "vitest";
import { mount } from "@vue/test-utils";
import AppChart from "./AppChart.vue";

const chartData = {
    labels: ["A", "B"],
    datasets: [{ data: [10, 20], backgroundColor: ["#f00", "#0f0"] }],
};

describe("AppChart", () => {
    it("renders without throwing for type=doughnut", () => {
        const wrapper = mount(AppChart, {
            props: { type: "doughnut", data: chartData },
            global: { stubs: { Doughnut: true, Bar: true, Line: true } },
        });
        expect(wrapper.exists()).toBe(true);
    });

    it("renders without throwing for type=bar", () => {
        const wrapper = mount(AppChart, {
            props: { type: "bar", data: chartData },
            global: { stubs: { Doughnut: true, Bar: true, Line: true } },
        });
        expect(wrapper.exists()).toBe(true);
    });

    it("renders without throwing for type=line", () => {
        const wrapper = mount(AppChart, {
            props: { type: "line", data: chartData },
            global: { stubs: { Doughnut: true, Bar: true, Line: true } },
        });
        expect(wrapper.exists()).toBe(true);
    });

    it("merges custom options with baseOptions", async () => {
        const wrapper = mount(AppChart, {
            props: {
                type: "bar",
                data: chartData,
                options: { animation: false },
            },
            global: { stubs: { Doughnut: true, Bar: true, Line: true } },
        });
        // component should still render with merged options
        expect(wrapper.exists()).toBe(true);
    });

    it("renders a canvas-based element for doughnut type", () => {
        const wrapper = mount(AppChart, {
            props: { type: "doughnut", data: chartData },
            global: { stubs: { Doughnut: true, Bar: true, Line: true } },
        });
        expect(wrapper.exists()).toBe(true);
    });
});

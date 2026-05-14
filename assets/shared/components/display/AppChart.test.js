import { describe, it, expect, vi } from "vitest";
import { mount } from "@vue/test-utils";

vi.mock("vue-chartjs", () => ({
    Doughnut: { template: "<canvas />" },
    Bar: { template: "<canvas />" },
    Line: { template: "<canvas />" },
}));

// Chart.js registers need to be silenced too
vi.mock("chart.js", () => ({
    Chart: { register: vi.fn() },
    ArcElement: {},
    Tooltip: {},
    Legend: {},
    CategoryScale: {},
    LinearScale: {},
    BarElement: {},
    LineElement: {},
    PointElement: {},
    Filler: {},
}));

import AppChart from "./AppChart.vue";

const chartData = {
    labels: ["A", "B"],
    datasets: [{ data: [10, 20], backgroundColor: ["#f00", "#0f0"] }],
};

describe("AppChart", () => {
    it("renders without throwing for type=doughnut", () => {
        expect(() => mount(AppChart, { props: { type: "doughnut", data: chartData } })).not.toThrow();
    });

    it("renders without throwing for type=bar", () => {
        expect(() => mount(AppChart, { props: { type: "bar", data: chartData } })).not.toThrow();
    });

    it("renders without throwing for type=line", () => {
        expect(() => mount(AppChart, { props: { type: "line", data: chartData } })).not.toThrow();
    });

    it("merges custom options without throwing", () => {
        expect(() => mount(AppChart, {
            props: { type: "bar", data: chartData, options: { animation: false } },
        })).not.toThrow();
    });

    it("renders a canvas element for the chart", () => {
        const wrapper = mount(AppChart, { props: { type: "doughnut", data: chartData } });
        expect(wrapper.find("canvas").exists()).toBe(true);
    });
});

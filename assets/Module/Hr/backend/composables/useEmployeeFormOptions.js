import { ref, onMounted } from "vue";
import { useApiRequest } from "@/shared/composables/api/useApiRequest.js";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";

export function useEmployeeFormOptions(
    servicesSelectablePath,
    agenciesSelectablePath,
    usersSelectablePath,
) {
    const { request: requestServices } = useApiRequest();
    const { request: requestAgencies } = useApiRequest();
    const { request: requestUsers } = useApiRequest();
    const serviceOptions = ref([]);
    const agencyOptions = ref([]);
    const userOptions = ref([]);

    onMounted(async () => {
        const [servicesData, agenciesData, usersData] = await Promise.all([
            requestServices(servicesSelectablePath, null, HttpMethod.Get),
            requestAgencies(agenciesSelectablePath, null, HttpMethod.Get),
            requestUsers(usersSelectablePath, null, HttpMethod.Get),
        ]);

        if (servicesData?.success)
            serviceOptions.value = servicesData.items ?? [];
        if (agenciesData?.success)
            agencyOptions.value = agenciesData.items ?? [];
        if (usersData?.success)
            userOptions.value = (usersData.items ?? []).map((user) => ({
                value: String(user.id),
                label: `${user.name} — ${user.email}`,
            }));
    });

    return { serviceOptions, agencyOptions, userOptions };
}

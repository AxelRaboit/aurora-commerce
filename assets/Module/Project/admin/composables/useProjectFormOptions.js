import { computed } from "vue";

/**
 * Builds the AppMultiselect option arrays from the props injected by the controller.
 */
export function useProjectFormOptions(props) {
    const userOptions = computed(() =>
        props.users.map((user) => ({ value: user.id, label: user.name })),
    );
    const contactOptions = computed(() =>
        props.crmContacts.map((contact) => ({
            value: contact.id,
            label: contact.name,
        })),
    );
    const companyOptions = computed(() =>
        props.crmCompanies.map((company) => ({
            value: company.id,
            label: company.name,
        })),
    );
    const dealOptions = computed(() =>
        (props.crmDeals ?? []).map((deal) => ({
            value: deal.id,
            label: deal.name,
        })),
    );

    return { userOptions, contactOptions, companyOptions, dealOptions };
}

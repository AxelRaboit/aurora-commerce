{% extends '@Core/backend/layout.html.twig' %}

{% block title %}{{ 'backend.nav.{{MODULE_ID}}'|trans }} - {{ parent() }}{% endblock %}

{% block page_header_slot %}
    {{ include('@Shared/components/page_header.html.twig', {
        crumbs: [
            {label: 'backend.nav.sections.{{MODULE_ID}}'|trans},
            {label: 'backend.nav.{{MODULE_ID}}'|trans},
        ],
    }) }}
{% endblock %}

{% block body %}
    <div {{ vue_component('{{MODULE_ID}}/backend/{{MODULE}}App', {}) }} class="flex-1 min-w-0"></div>
{% endblock %}

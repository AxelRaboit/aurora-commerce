<script setup>
import { computed, ref, onMounted, onBeforeUnmount } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppOverlayIconButton from "@/shared/components/action/AppOverlayIconButton.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppTextarea from "@/shared/components/form/AppTextarea.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import { translateServerErrors } from "@/shared/utils/validation/translateServerErrors.js";
import {
    Heart,
    Printer,
    Trash2,
    MessageSquare,
    Download,
    Archive,
    ChevronLeft,
    ChevronRight,
    X,
    Check,
    Send,
    Columns2,
    Share2,
    Copy,
} from "lucide-vue-next";

const { t } = useI18n();

const props = defineProps({
    gallery: { type: Object, required: true },
    items: { type: Array, required: true },
    visitorPicks: { type: Object, default: () => ({}) },
    favoriteCount: { type: Number, default: 0 },
    visitorIdentity: { type: Object, default: () => ({ name: null, email: null }) },
    finalizedByVisitor: { type: Boolean, default: false },
    readOnly: { type: Boolean, default: false },
    sharePath: { type: String, default: null },
    pickPath: { type: String, required: true },
    commentPath: { type: String, default: "" },
    finalizePath: { type: String, required: true },
    downloadItemPath: { type: String, required: true },
    downloadZipPath: { type: String, required: true },
});

const KIND = { Favorite: "favorite", Print: "print", Discard: "discard" };

function buildPickedSets(initial) {
    const sets = { favorite: new Set(), print: new Set(), discard: new Set() };
    for (const [id, kinds] of Object.entries(initial ?? {})) {
        const itemId = Number(id);
        for (const kind of kinds) {
            if (sets[kind]) sets[kind].add(itemId);
        }
    }
    return sets;
}

const picked = ref(buildPickedSets(props.visitorPicks));
const favoriteTotal = ref(props.favoriteCount);
const lightboxIndex = ref(null);
// Per-visitor lock (this user has validated their own selection) OR global
// lock set by the photographer on the gallery — both freeze the UI.
const finalized = ref(!!props.gallery.finalized || !!props.finalizedByVisitor);

// Identity capture — pre-filled from prior picks when the visitor already
// provided their name/email on this gallery.
const visitorName = ref(props.visitorIdentity?.name ?? "");
const visitorEmail = ref(props.visitorIdentity?.email ?? "");
const identityKnown = ref(Boolean(visitorName.value && visitorEmail.value));
const showIdentityModal = ref(false);
const pendingPick = ref(null); // { itemId, kind }

// Finalize modal
const showFinalizeModal = ref(false);
const finalizeName = ref("");
const finalizeEmail = ref("");
const finalizing = ref(false);
const finalizeNameError = ref("");
const finalizeEmailError = ref("");

// Side-by-side comparison (max 4 photos)
const COMPARE_MAX = 4;
const compareIds = ref([]);
const showCompare = ref(false);

function isCompared(itemId) {
    return compareIds.value.includes(Number(itemId));
}

function toggleCompare(itemId) {
    const id = Number(itemId);
    const idx = compareIds.value.indexOf(id);
    if (idx >= 0) {
        compareIds.value = compareIds.value.filter((x) => x !== id);
        return;
    }
    if (compareIds.value.length >= COMPARE_MAX) {
        toast.error(t("photo.frontend.compare.max", { max: COMPARE_MAX }));
        return;
    }
    compareIds.value = [...compareIds.value, id];
}

function clearCompare() {
    compareIds.value = [];
    showCompare.value = false;
}

const compareItems = computed(() =>
    compareIds.value
        .map((id) => props.items.find((i) => i.id === id))
        .filter(Boolean),
);

const compareGridClass = computed(() => {
    const n = compareItems.value.length;
    if (n <= 1) return "grid-cols-1";
    if (n === 2) return "grid-cols-2";
    return "grid-cols-2 grid-rows-2";
});

// In read-only/shared mode, only show the picks the visitor validated — that's
// the whole point of "share my selection": the recipient sees the curated list,
// not the entire gallery.
const displayedItems = computed(() => {
    if (!props.readOnly) return props.items;
    return props.items.filter(
        (i) => isPicked(i.id, KIND.Favorite) || isPicked(i.id, KIND.Print) || isPicked(i.id, KIND.Discard),
    );
});

// Share my selection
const showShareModal = ref(false);
const shareCopied = ref(false);
const shareFullUrl = computed(() => props.sharePath ? window.location.origin + props.sharePath : "");

async function copyShareUrl() {
    if (!shareFullUrl.value) return;
    try {
        await navigator.clipboard.writeText(shareFullUrl.value);
        shareCopied.value = true;
        setTimeout(() => { shareCopied.value = false; }, 2500);
    } catch {
        toast.error(t("shared.common.error"));
    }
}

// Comment composer (per lightbox item)
const showCommentBox = ref(false);
const commentDraft = ref("");
const commentSending = ref(false);
const commentNameError = ref("");
const commentEmailError = ref("");

// Burst grouping: assign each item a `burstId` (1-based) when its takenAt is
// within 2s of the previous item's takenAt — visitors then see consecutive
// near-duplicate frames as a labeled run instead of having to compare each.
const BURST_THRESHOLD_MS = 2000;
const burstMap = computed(() => {
    const map = new Map();
    let currentBurstId = 0;
    let currentBurstStart = null;
    let prevTime = null;
    const burstCounts = new Map();

    for (const item of props.items) {
        const t = item.takenAt ? new Date(item.takenAt).getTime() : NaN;
        if (Number.isFinite(t) && Number.isFinite(prevTime) && (t - prevTime) <= BURST_THRESHOLD_MS) {
            // Same burst — promote previous solo item if needed
            if (currentBurstId === 0) {
                currentBurstId = (burstCounts.size + 1);
                map.set(currentBurstStart, currentBurstId);
                burstCounts.set(currentBurstId, 1);
            }
            map.set(item.id, currentBurstId);
            burstCounts.set(currentBurstId, (burstCounts.get(currentBurstId) ?? 0) + 1);
        } else {
            // Burst ends; start tracking the next candidate
            currentBurstId = 0;
            currentBurstStart = item.id;
        }
        prevTime = t;
    }

    return { ids: map, counts: burstCounts };
});

function burstIdOf(item) {
    return burstMap.value.ids.get(item.id) ?? null;
}

function burstSizeOf(burstId) {
    return burstMap.value.counts.get(burstId) ?? 0;
}

function burstIndexOf(item, burstId) {
    let i = 0;
    for (const it of props.items) {
        if (burstMap.value.ids.get(it.id) === burstId) {
            i++;
            if (it.id === item.id) return i;
        }
    }
    return 0;
}

const maxPicks = computed(() => props.gallery.maxPicks ?? null);
const favoriteCountReached = computed(
    () => maxPicks.value !== null && favoriteTotal.value >= maxPicks.value,
);


function isPicked(id, kind = KIND.Favorite) {
    return picked.value[kind]?.has(Number(id)) ?? false;
}

function openLightbox(index) {
    lightboxIndex.value = index;
    showCommentBox.value = false;
    commentDraft.value = "";
}

function closeLightbox() {
    lightboxIndex.value = null;
}

function prev() {
    if (lightboxIndex.value === null) return;
    const len = displayedItems.value.length;
    lightboxIndex.value = (lightboxIndex.value - 1 + len) % len;
    showCommentBox.value = false;
    commentDraft.value = "";
}

function next() {
    if (lightboxIndex.value === null) return;
    const len = displayedItems.value.length;
    lightboxIndex.value = (lightboxIndex.value + 1) % len;
    showCommentBox.value = false;
    commentDraft.value = "";
}

function onKeydown(event) {
    if (lightboxIndex.value === null) return;
    if (event.key === "Escape") closeLightbox();
    else if (event.key === "ArrowLeft") prev();
    else if (event.key === "ArrowRight") next();
}

onMounted(() => window.addEventListener("keydown", onKeydown));
onBeforeUnmount(() => window.removeEventListener("keydown", onKeydown));

async function togglePick(itemId, kind = KIND.Favorite) {
    if (finalized.value) {
        toast.info(t("photo.frontend.alreadyFinalized"));
        return;
    }

    if (props.gallery.picksRequireIdentity && !identityKnown.value) {
        pendingPick.value = { itemId, kind };
        showIdentityModal.value = true;
        return;
    }

    await sendToggle(itemId, kind);
}

async function sendToggle(itemId, kind, { name = null, email = null } = {}) {
    try {
        const response = await fetch(buildPath(props.pickPath, { id: itemId }), {
            method: HttpMethod.Post,
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ name, email, kind }),
        });

        if (response.status === 422) {
            const data = await response.json().catch(() => null);
            if (data?.error === "identity_required") {
                pendingPick.value = { itemId, kind };
                showIdentityModal.value = true;
                identityKnown.value = false;
                return;
            }
        }

        if (response.status === 409) {
            const data = await response.json().catch(() => null);
            if (data?.error === "max_picks_reached") {
                toast.error(t("photo.galleries.errors.max_picks_reached", { limit: data.limit }));
                return;
            }
            if (data?.error === "finalized") {
                finalized.value = true;
                toast.info(t("photo.frontend.alreadyFinalized"));
                return;
            }
        }

        const data = await response.json();
        if (!data?.success) {
            toast.error(t("shared.common.error"));
            return;
        }

        const set = picked.value[kind];
        if (data.picked) set.add(Number(itemId));
        else set.delete(Number(itemId));
        picked.value = { ...picked.value, [kind]: new Set(set) };
        if (typeof data.favoriteCount === "number") favoriteTotal.value = data.favoriteCount;
    } catch {
        toast.error(t("shared.common.error"));
    }
}

async function submitIdentity() {
    if (!visitorName.value.trim() || !visitorEmail.value.trim()) return;

    identityKnown.value = true;
    showIdentityModal.value = false;
    if (pendingPick.value !== null) {
        const { itemId, kind } = pendingPick.value;
        await sendToggle(itemId, kind, { name: visitorName.value, email: visitorEmail.value });
        pendingPick.value = null;
    }
}

function openFinalize() {
    if (props.gallery.picksRequireIdentity || identityKnown.value) {
        finalizeName.value = visitorName.value;
        finalizeEmail.value = visitorEmail.value;
    }
    showFinalizeModal.value = true;
}

async function submitFinalize() {
    finalizeNameError.value = "";
    finalizeEmailError.value = "";
    finalizing.value = true;
    try {
        const response = await fetch(props.finalizePath, {
            method: HttpMethod.Post,
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                name: finalizeName.value,
                email: finalizeEmail.value,
            }),
        });
        const data = await response.json();
        if (!data?.success) {
            const errors = translateServerErrors(t, data?.errors);
            finalizeNameError.value = errors.name ?? "";
            finalizeEmailError.value = errors.email ?? "";
            if (!errors.name && !errors.email) toast.error(t("shared.common.error"));
            return;
        }
        finalized.value = true;
        showFinalizeModal.value = false;
        toast.success(t("photo.frontend.finalizedToast"));
    } catch {
        toast.error(t("shared.common.error"));
    } finally {
        finalizing.value = false;
    }
}

async function submitComment() {
    if (lightboxIndex.value === null) return;

    commentNameError.value = "";
    commentEmailError.value = "";

    if (!commentDraft.value.trim()) {
        toast.error(t("photo.frontend.comments.contentRequired"));
        return;
    }
    if (!visitorName.value.trim()) {
        commentNameError.value = t("photo.frontend.comments.nameRequired");
        return;
    }
    if (!visitorEmail.value.trim()) {
        commentEmailError.value = t("photo.frontend.comments.emailRequired");
        return;
    }

    const itemId = displayedItems.value[lightboxIndex.value].id;
    commentSending.value = true;
    try {
        const response = await fetch(buildPath(props.commentPath, { id: itemId }), {
            method: HttpMethod.Post,
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                content: commentDraft.value,
                name: visitorName.value,
                email: visitorEmail.value,
            }),
        });
        const data = await response.json();
        if (!data?.success) {
            const errors = translateServerErrors(t, data?.errors);
            if (errors.visitorEmail) commentEmailError.value = errors.visitorEmail;
            else toast.error(t("shared.common.error"));
            return;
        }
        // Capture the identity for subsequent flows (picks, finalize) so the
        // visitor doesn't have to re-type it.
        identityKnown.value = true;
        toast.success(t("photo.frontend.comments.sent"));
        commentDraft.value = "";
        showCommentBox.value = false;
    } catch {
        toast.error(t("shared.common.error"));
    } finally {
        commentSending.value = false;
    }
}

function downloadAll(onlyPicks = false) {
    const url = new URL(props.downloadZipPath, window.location.origin);
    if (onlyPicks) url.searchParams.set("picks", "1");
    if (props.gallery.allowOriginals) url.searchParams.set("variant", "original");
    window.location.href = url.toString();
}

function downloadOne(itemId) {
    const url = new URL(buildPath(props.downloadItemPath, { id: itemId }), window.location.origin);
    if (props.gallery.allowOriginals) url.searchParams.set("variant", "original");
    window.location.href = url.toString();
}
</script>

<template>
    <div class="max-w-7xl mx-auto px-4 py-8 space-y-8 photo-protected">
        <!-- Header -->
        <header class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-primary">{{ gallery.title }}</h1>
                <p v-if="gallery.description" class="text-muted text-sm mt-1 max-w-2xl">{{ gallery.description }}</p>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <span v-if="maxPicks" class="text-sm" :class="favoriteCountReached ? 'text-rose-500' : 'text-muted'">
                    {{ t("photo.frontend.maxPicksProgress", { count: favoriteTotal, limit: maxPicks }) }}
                </span>
                <span v-else class="text-sm text-muted flex items-center gap-1.5">
                    <Heart class="w-4 h-4 text-accent-500" :stroke-width="2" fill="currentColor" />
                    {{ favoriteTotal }} {{ t("photo.frontend.picked") }}
                </span>
                <AppButton
                    v-if="gallery.allowZipDownload && !readOnly"
                    variant="secondary"
                    size="sm"
                    type="button"
                    v-on:click="downloadAll(false)"
                >
                    <Archive class="w-4 h-4" :stroke-width="2" />
                    {{ t("photo.frontend.downloadAll") }}
                </AppButton>
                <AppButton
                    v-if="gallery.allowZipDownload && favoriteTotal > 0 && !readOnly"
                    variant="secondary"
                    size="sm"
                    type="button"
                    v-on:click="downloadAll(true)"
                >
                    <Download class="w-4 h-4" :stroke-width="2" />
                    {{ t("photo.frontend.downloadPicks") }}
                </AppButton>
                <AppButton
                    v-if="!finalized && favoriteTotal > 0 && !readOnly"
                    variant="primary"
                    size="sm"
                    type="button"
                    v-on:click="openFinalize"
                >
                    <Send class="w-4 h-4" :stroke-width="2" />
                    {{ t("photo.frontend.finalize") }}
                </AppButton>
                <AppButton
                    v-if="sharePath && favoriteTotal > 0 && !readOnly"
                    variant="ghost"
                    size="sm"
                    type="button"
                    v-on:click="showShareModal = true"
                >
                    <Share2 class="w-4 h-4" :stroke-width="2" />
                    {{ t("photo.frontend.share.button") }}
                </AppButton>
                <span v-if="finalized && !readOnly" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-500/15 text-emerald-500 text-xs font-medium">
                    <Check class="w-3.5 h-3.5" :stroke-width="2.5" />
                    {{ t("photo.frontend.finalizedBadge") }}
                </span>
                <span v-if="readOnly" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-500/15 text-blue-500 text-xs font-medium">
                    <Share2 class="w-3.5 h-3.5" :stroke-width="2.5" />
                    {{ t("photo.frontend.share.readOnlyBadge") }}
                </span>
            </div>
        </header>

        <!-- Grid -->
        <div v-if="displayedItems.length === 0" class="text-center text-muted py-20">
            {{ t("photo.frontend.empty") }}
        </div>
        <div v-else class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2">
            <button
                v-for="(item, index) in displayedItems"
                :key="item.id"
                type="button"
                class="group relative aspect-square overflow-hidden rounded-md bg-surface-2 focus:outline-none focus:ring-2 focus:ring-accent-500"
                v-on:click="openLightbox(index)"
            >
                <img
                    :src="item.thumb"
                    :alt="item.alt || ''"
                    loading="lazy"
                    draggable="false"
                    class="w-full h-full object-cover transition-transform group-hover:scale-105 photo-protected-img"
                    v-on:contextmenu.prevent
                >
                <span v-if="item.number" class="absolute bottom-1.5 left-1.5 px-1.5 py-0.5 rounded bg-black/55 text-white text-[10px] font-semibold tabular-nums tracking-wide">
                    #{{ item.number }}
                </span>
                <span
                    v-if="burstIdOf(item)"
                    class="absolute top-1.5 left-1.5 px-1.5 py-0.5 rounded bg-amber-500/90 text-white text-[10px] font-semibold tabular-nums shadow"
                    :title="t('photo.frontend.burst.tooltip', { size: burstSizeOf(burstIdOf(item)) })"
                >
                    {{ burstIndexOf(item, burstIdOf(item)) }}/{{ burstSizeOf(burstIdOf(item)) }}
                </span>
                <AppOverlayIconButton
                    v-if="!finalized"
                    size="sm"
                    :active="isPicked(item.id, KIND.Favorite)"
                    :aria-label="isPicked(item.id, KIND.Favorite) ? t('photo.frontend.unpick') : t('photo.frontend.pick')"
                    class="absolute top-1.5 right-1.5"
                    v-on:click.stop="togglePick(item.id, KIND.Favorite)"
                >
                    <Heart class="w-4 h-4" :stroke-width="2" :fill="isPicked(item.id, KIND.Favorite) ? 'currentColor' : 'none'" />
                </AppOverlayIconButton>
                <span
                    v-else-if="isPicked(item.id, KIND.Favorite)"
                    class="absolute top-1.5 right-1.5 inline-flex items-center justify-center w-8 h-8 rounded-full bg-black/40 text-accent-500"
                >
                    <Heart class="w-4 h-4" :stroke-width="2" fill="currentColor" />
                </span>
                <AppOverlayIconButton
                    size="sm"
                    :active="isCompared(item.id)"
                    :aria-label="isCompared(item.id) ? t('photo.frontend.compare.remove') : t('photo.frontend.compare.add')"
                    class="absolute top-1.5 left-1.5 transition-opacity"
                    :class="isCompared(item.id) ? 'opacity-100' : 'opacity-0 group-hover:opacity-100'"
                    v-on:click.stop="toggleCompare(item.id)"
                >
                    <Columns2 class="w-4 h-4" :stroke-width="2" />
                </AppOverlayIconButton>
            </button>
        </div>

        <!-- Sticky favorites progress -->
        <div
            v-if="maxPicks && lightboxIndex === null && !showCompare"
            class="fixed top-4 right-4 z-30 flex items-center gap-2 px-3 py-1.5 rounded-full bg-surface/90 backdrop-blur border border-line shadow-md"
        >
            <Heart class="w-4 h-4" :stroke-width="2" :fill="favoriteCountReached ? 'currentColor' : 'none'" :class="favoriteCountReached ? 'text-rose-500' : 'text-accent-500'" />
            <span class="text-sm font-semibold tabular-nums" :class="favoriteCountReached ? 'text-rose-500' : 'text-primary'">
                {{ favoriteTotal }}<span class="text-muted font-normal">/{{ maxPicks }}</span>
            </span>
        </div>

        <!-- Floating compare bar -->
        <div
            v-if="compareIds.length > 0 && !showCompare"
            class="fixed bottom-4 left-1/2 -translate-x-1/2 z-40 flex items-center gap-2 px-4 py-2 rounded-full bg-accent-500 text-white shadow-lg"
        >
            <Columns2 class="w-4 h-4" :stroke-width="2" />
            <span class="text-sm font-medium">{{ t("photo.frontend.compare.selected", { count: compareIds.length, max: COMPARE_MAX }) }}</span>
            <button type="button" class="text-sm font-medium underline ml-2" v-on:click="showCompare = true">
                {{ t("photo.frontend.compare.open") }}
            </button>
            <button type="button" class="ml-1 opacity-80 hover:opacity-100" :aria-label="t('photo.frontend.compare.clear')" v-on:click="clearCompare">
                <X class="w-4 h-4" :stroke-width="2" />
            </button>
        </div>

        <!-- Compare modal -->
        <div
            v-if="showCompare"
            class="fixed inset-0 z-50 bg-black/95 flex flex-col"
            v-on:click.self="showCompare = false"
        >
            <div class="flex items-center justify-between p-4 text-white">
                <span class="text-sm font-medium">{{ t("photo.frontend.compare.title") }} ({{ compareItems.length }})</span>
                <div class="flex items-center gap-2">
                    <button type="button" class="text-sm underline" v-on:click="clearCompare">
                        {{ t("photo.frontend.compare.clear") }}
                    </button>
                    <AppOverlayIconButton size="md" variant="light" :title="t('shared.common.close')" v-on:click="showCompare = false">
                        <X class="w-5 h-5" :stroke-width="2" />
                    </AppOverlayIconButton>
                </div>
            </div>
            <div class="flex-1 min-h-0 p-3 overflow-auto">
                <div class="grid h-full gap-2" :class="compareGridClass">
                    <div v-for="item in compareItems" :key="item.id" class="relative bg-black rounded overflow-hidden">
                        <img
                            :src="item.medium ?? item.thumb"
                            :alt="item.alt || ''"
                            class="w-full h-full object-contain photo-protected-img"
                            draggable="false"
                            v-on:contextmenu.prevent
                        >
                        <span v-if="item.number" class="absolute top-2 left-2 px-2 py-1 rounded bg-black/60 text-white text-xs font-semibold tabular-nums">
                            #{{ item.number }}
                        </span>
                        <button
                            type="button"
                            class="absolute top-2 right-2 inline-flex items-center justify-center w-8 h-8 rounded-full bg-black/60 text-white hover:bg-black/80"
                            :title="t('photo.frontend.compare.remove')"
                            v-on:click="toggleCompare(item.id)"
                        >
                            <X class="w-4 h-4" :stroke-width="2" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lightbox -->
        <div
            v-if="lightboxIndex !== null"
            class="fixed inset-0 z-50 bg-black/95 flex flex-col"
            v-on:click.self="closeLightbox"
        >
            <div class="flex items-center justify-between p-4 text-white">
                <span class="text-sm tabular-nums">
                    <span v-if="displayedItems[lightboxIndex].number" class="font-semibold mr-2">#{{ displayedItems[lightboxIndex].number }}</span>
                    <span class="opacity-70">{{ lightboxIndex + 1 }} / {{ displayedItems.length }}</span>
                </span>
                <div class="flex items-center gap-2">
                    <template v-if="!finalized">
                        <AppOverlayIconButton
                            size="md"
                            variant="light"
                            :active="isPicked(displayedItems[lightboxIndex].id, KIND.Favorite)"
                            :title="t('photo.frontend.kinds.favorite')"
                            v-on:click="togglePick(displayedItems[lightboxIndex].id, KIND.Favorite)"
                        >
                            <Heart class="w-5 h-5" :stroke-width="2" :fill="isPicked(displayedItems[lightboxIndex].id, KIND.Favorite) ? 'currentColor' : 'none'" />
                        </AppOverlayIconButton>
                        <AppOverlayIconButton
                            size="md"
                            variant="light"
                            :active="isPicked(displayedItems[lightboxIndex].id, KIND.Print)"
                            :title="t('photo.frontend.kinds.print')"
                            v-on:click="togglePick(displayedItems[lightboxIndex].id, KIND.Print)"
                        >
                            <Printer class="w-5 h-5" :stroke-width="2" />
                        </AppOverlayIconButton>
                        <AppOverlayIconButton
                            size="md"
                            variant="light"
                            :active="isPicked(displayedItems[lightboxIndex].id, KIND.Discard)"
                            :title="t('photo.frontend.kinds.discard')"
                            v-on:click="togglePick(displayedItems[lightboxIndex].id, KIND.Discard)"
                        >
                            <Trash2 class="w-5 h-5" :stroke-width="2" />
                        </AppOverlayIconButton>
                    </template>
                    <!-- Read-only state on finalized galleries: surface what was picked, no toggling -->
                    <template v-else>
                        <span
                            v-if="isPicked(displayedItems[lightboxIndex].id, KIND.Favorite)"
                            class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white/10 text-accent-500"
                            :title="t('photo.frontend.kinds.favorite')"
                        >
                            <Heart class="w-5 h-5" :stroke-width="2" fill="currentColor" />
                        </span>
                        <span
                            v-if="isPicked(displayedItems[lightboxIndex].id, KIND.Print)"
                            class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white/10 text-blue-400"
                            :title="t('photo.frontend.kinds.print')"
                        >
                            <Printer class="w-5 h-5" :stroke-width="2" />
                        </span>
                        <span
                            v-if="isPicked(displayedItems[lightboxIndex].id, KIND.Discard)"
                            class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white/10 text-rose-400"
                            :title="t('photo.frontend.kinds.discard')"
                        >
                            <Trash2 class="w-5 h-5" :stroke-width="2" />
                        </span>
                    </template>
                    <AppOverlayIconButton
                        v-if="gallery.allowVisitorComments && !finalized"
                        size="md"
                        variant="light"
                        :active="showCommentBox"
                        :title="t('photo.frontend.comments.add')"
                        v-on:click="showCommentBox = !showCommentBox"
                    >
                        <MessageSquare class="w-5 h-5" :stroke-width="2" />
                    </AppOverlayIconButton>
                    <AppOverlayIconButton
                        size="md"
                        variant="light"
                        :title="t('photo.frontend.download')"
                        v-on:click="downloadOne(displayedItems[lightboxIndex].id)"
                    >
                        <Download class="w-5 h-5" :stroke-width="2" />
                    </AppOverlayIconButton>
                    <AppOverlayIconButton
                        size="md"
                        variant="light"
                        :title="t('shared.common.close')"
                        v-on:click="closeLightbox"
                    >
                        <X class="w-5 h-5" :stroke-width="2" />
                    </AppOverlayIconButton>
                </div>
            </div>
            <div class="flex-1 flex items-center justify-center px-4 pb-4 relative">
                <AppOverlayIconButton
                    size="lg"
                    variant="light"
                    :title="t('shared.common.previous')"
                    class="absolute left-2 top-1/2 -translate-y-1/2"
                    v-on:click="prev"
                >
                    <ChevronLeft class="w-6 h-6" :stroke-width="2" />
                </AppOverlayIconButton>
                <div class="relative max-h-[80vh] max-w-full">
                    <img
                        :src="displayedItems[lightboxIndex].full"
                        :alt="displayedItems[lightboxIndex].alt || ''"
                        draggable="false"
                        class="max-h-[80vh] max-w-full object-contain photo-protected-img"
                        v-on:contextmenu.prevent
                    >
                </div>
                <AppOverlayIconButton
                    size="lg"
                    variant="light"
                    :title="t('shared.common.next')"
                    class="absolute right-2 top-1/2 -translate-y-1/2"
                    v-on:click="next"
                >
                    <ChevronRight class="w-6 h-6" :stroke-width="2" />
                </AppOverlayIconButton>
            </div>

            <!-- Caption -->
            <p
                v-if="displayedItems[lightboxIndex].caption"
                class="text-center text-white/80 text-sm pb-6 px-4 max-w-3xl mx-auto"
            >
                {{ displayedItems[lightboxIndex].caption }}
            </p>
        </div>

        <!-- Identity modal -->
        <AppModal :show="showIdentityModal" v-on:close="showIdentityModal = false">
            <h3 class="text-lg font-semibold text-primary mb-4">{{ t('photo.frontend.identity.title') }}</h3>
            <form class="space-y-4" v-on:submit.prevent="submitIdentity">
                <p class="text-sm text-muted">{{ t("photo.frontend.identity.intro") }}</p>
                <AppInput v-model="visitorName" :label="t('photo.frontend.identity.name')" required />
                <AppInput v-model="visitorEmail" type="email" :label="t('photo.frontend.identity.email')" required />
                <AppModalFooter>
                    <AppButton variant="ghost" type="button" v-on:click="showIdentityModal = false">{{ t("shared.common.cancel") }}</AppButton>
                    <AppButton type="submit">{{ t("photo.frontend.identity.submit") }}</AppButton>
                </AppModalFooter>
            </form>
        </AppModal>

        <!-- Comment modal -->
        <AppModal :show="showCommentBox" v-on:close="showCommentBox = false">
            <h3 class="text-lg font-semibold text-primary mb-4 flex items-center gap-2">
                <MessageSquare class="w-5 h-5 text-accent-500" :stroke-width="2" />
                {{ t("photo.frontend.comments.add") }}
            </h3>
            <form class="space-y-4" v-on:submit.prevent="submitComment">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <AppInput
                        v-model="visitorName"
                        :label="t('photo.frontend.identity.name')"
                        :placeholder="t('photo.frontend.identity.namePlaceholder')"
                        :error="commentNameError"
                        required
                    />
                    <AppInput
                        v-model="visitorEmail"
                        type="email"
                        :label="t('photo.frontend.identity.email')"
                        :placeholder="t('photo.frontend.identity.emailPlaceholder')"
                        :error="commentEmailError"
                        required
                    />
                </div>
                <AppTextarea
                    v-model="commentDraft"
                    :label="t('photo.frontend.comments.label')"
                    :placeholder="t('photo.frontend.comments.placeholder')"
                    :rows="4"
                    maxlength="2000"
                />
                <AppModalFooter>
                    <AppButton variant="ghost" type="button" v-on:click="showCommentBox = false">
                        {{ t("photo.frontend.comments.cancel") }}
                    </AppButton>
                    <AppButton variant="primary" type="submit" :loading="commentSending">
                        <Send class="w-4 h-4" :stroke-width="2" />
                        {{ t("photo.frontend.comments.submit") }}
                    </AppButton>
                </AppModalFooter>
            </form>
        </AppModal>

        <!-- Share my selection modal -->
        <AppModal :show="showShareModal" v-on:close="showShareModal = false">
            <h3 class="text-lg font-semibold text-primary mb-2">{{ t('photo.frontend.share.title') }}</h3>
            <p class="text-sm text-muted mb-4">{{ t("photo.frontend.share.intro", { count: favoriteTotal }) }}</p>
            <div class="flex items-center gap-2 p-2 rounded-lg bg-surface-2 border border-line">
                <input
                    type="text"
                    readonly
                    :value="shareFullUrl"
                    class="flex-1 bg-transparent text-sm text-primary outline-none truncate"
                    v-on:focus="$event.target.select()"
                >
                <AppButton variant="primary" size="sm" type="button" v-on:click="copyShareUrl">
                    <Copy class="w-4 h-4" :stroke-width="2" />
                    {{ shareCopied ? t("photo.frontend.share.copied") : t("photo.frontend.share.copy") }}
                </AppButton>
            </div>
            <AppModalFooter>
                <AppButton variant="ghost" type="button" v-on:click="showShareModal = false">{{ t("shared.common.close") }}</AppButton>
            </AppModalFooter>
        </AppModal>

        <!-- Finalize modal -->
        <AppModal :show="showFinalizeModal" v-on:close="showFinalizeModal = false">
            <h3 class="text-lg font-semibold text-primary mb-4">{{ t('photo.frontend.finalize') }}</h3>
            <form class="space-y-4" v-on:submit.prevent="submitFinalize">
                <p class="text-sm text-muted">{{ t("photo.frontend.finalizeIntro", { count: favoriteTotal }) }}</p>
                <AppInput
                    v-model="finalizeName"
                    :label="t('photo.frontend.identity.name')"
                    :placeholder="t('photo.frontend.identity.namePlaceholder')"
                    :error="finalizeNameError"
                    required
                />
                <AppInput
                    v-model="finalizeEmail"
                    type="email"
                    :label="t('photo.frontend.identity.email')"
                    :placeholder="t('photo.frontend.identity.emailPlaceholder')"
                    :error="finalizeEmailError"
                    required
                />
                <AppModalFooter>
                    <AppButton variant="ghost" type="button" v-on:click="showFinalizeModal = false">{{ t("shared.common.cancel") }}</AppButton>
                    <AppButton type="submit" :loading="finalizing">
                        <Send class="w-4 h-4" :stroke-width="2" />
                        {{ t("photo.frontend.finalize") }}
                    </AppButton>
                </AppModalFooter>
            </form>
        </AppModal>
    </div>
</template>

<style scoped>
/* Visual deterrents — the photos can still be reached via the URL or DevTools,
   but casual save-to-disk paths (right-click, drag, text-select) are blocked. */
.photo-protected {
    user-select: none;
    -webkit-user-select: none;
}

.photo-protected-img {
    -webkit-user-drag: none;
    user-drag: none;
    pointer-events: auto;
}

@media print {
    .photo-protected-img { display: none !important; }
}
</style>

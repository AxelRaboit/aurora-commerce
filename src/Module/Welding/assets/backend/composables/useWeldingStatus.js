/**
 * Single source of truth for the Welding status colour / icon / order tables.
 * Three independent status spaces:
 *  - Workflow (instance):       Draft / InProgress / AwaitingValidation / Completed / Rejected / Archived
 *  - WorkflowStep (instance):   Pending / InProgress / AwaitingValidation / Validated / Rejected
 *  - WorkflowTemplate:          Draft / Published / Archived
 *
 * All colour classes carry their dark-mode variant so callers don't have to
 * remember to add it.
 */
import {
    Circle,
    Clock,
    AlertCircle,
    CheckCircle2,
    XCircle,
    Send,
    Archive,
} from "lucide-vue-next";

export function useWorkflowStatus() {
    return {
        ORDER: ["draft", "in_progress", "awaiting_validation", "completed", "rejected", "archived"],
        COLOR: {
            draft: "bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300",
            in_progress: "bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300",
            awaiting_validation: "bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300",
            completed: "bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300",
            rejected: "bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300",
            archived: "bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400",
        },
    };
}

export function useStepStatus() {
    return {
        ICON: {
            pending: Circle,
            in_progress: Clock,
            awaiting_validation: AlertCircle,
            validated: CheckCircle2,
            rejected: XCircle,
        },
        COLOR: {
            pending: "text-muted",
            in_progress: "text-blue-500 dark:text-blue-400",
            awaiting_validation: "text-amber-500 dark:text-amber-400",
            validated: "text-emerald-500 dark:text-emerald-400",
            rejected: "text-rose-500 dark:text-rose-400",
        },
        BG: {
            pending: "bg-gray-50 dark:bg-gray-900/30 border-line",
            in_progress: "bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800",
            awaiting_validation: "bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800",
            validated: "bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-800",
            rejected: "bg-rose-50 dark:bg-rose-900/20 border-rose-200 dark:border-rose-800",
        },
    };
}

export function useTemplateStatus() {
    return {
        BADGE: {
            draft: "bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300",
            published: "bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300",
            archived: "bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400",
        },
        ICON: {
            draft: Send,
            published: CheckCircle2,
            archived: Archive,
        },
    };
}

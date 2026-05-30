#!/usr/bin/env bash
#
# Rollout: split each extracted module's subtree out of the aurora-core monorepo
# and push it to its own GitHub repo (one Composer package per repo).
#
# Prerequisites (do these FIRST):
#   1. Create the empty GitHub repos (no auto README/license) under the org:
#        aurora-crm aurora-billing aurora-editorial aurora-photo aurora-project
#        aurora-hr aurora-notes aurora-personal-finance aurora-planning
#        aurora-assistant aurora-commerce
#      (aurora-tools already done; aurora-core IS this monorepo.)
#   2. Be on a clean `develop` (or the branch you publish from) with every
#      module's composer.json + config/services.php committed.
#
# Usage:
#   bin/split-modules.sh              # split + push every module
#   bin/split-modules.sh aurora-crm   # just one (repo name or "commerce")
#
# Idempotent: re-running force-pushes the same content. Single-prefix modules
# keep full git history; aurora-commerce is a snapshot (two prefixes combined).
#
set -uo pipefail

ORG="git@github.com:AxelRaboit"
TARGET_BRANCH="main"

# repo-name => module dir (single-prefix modules)
SINGLE=(
    "aurora-crm:src/Module/Crm"
    "aurora-billing:src/Module/Billing"
    "aurora-editorial:src/Module/Editorial"
    "aurora-photo:src/Module/Photo"
    "aurora-project:src/Module/Project"
    "aurora-hr:src/Module/Hr"
    "aurora-notes:src/Module/Notes"
    "aurora-personal-finance:src/Module/PersonalFinance"
    "aurora-planning:src/Module/Planning"
    "aurora-assistant:src/Module/Assistant"
)

only="${1:-}"
ok=(); failed=()

split_single() {
    local repo="$1" prefix="$2" branch="split-$1"
    echo ">> $repo  ($prefix)"
    git branch -D "$branch" >/dev/null 2>&1 || true
    if ! git subtree split --prefix="$prefix" -b "$branch" >/dev/null 2>&1; then
        echo "   !! subtree split failed"; failed+=("$repo"); return
    fi
    if git push -f "${ORG}/${repo}.git" "${branch}:${TARGET_BRANCH}" 2>/dev/null; then
        echo "   ok -> ${ORG}/${repo}.git"; ok+=("$repo")
    else
        echo "   !! push failed (repo created? access?)"; failed+=("$repo")
    fi
    git branch -D "$branch" >/dev/null 2>&1 || true
}

# aurora-commerce = Ecommerce + Erp combined under Ecommerce/ + Erp/ subdirs,
# with a root composer.json mapping both namespaces. Snapshot (single commit).
split_commerce() {
    local repo="aurora-commerce" work="split-aurora-commerce"
    echo ">> $repo  (src/Module/Ecommerce + src/Module/Erp)"
    local eco erp
    eco="$(git subtree split --prefix=src/Module/Ecommerce 2>/dev/null)"
    erp="$(git subtree split --prefix=src/Module/Erp 2>/dev/null)"
    if [ -z "$eco" ] || [ -z "$erp" ]; then
        echo "   !! subtree split failed"; failed+=("$repo"); return
    fi
    git worktree remove --force ".commerce-build" >/dev/null 2>&1 || true
    rm -rf .commerce-build
    git worktree add --no-checkout -B "$work" .commerce-build HEAD >/dev/null 2>&1
    (
        cd .commerce-build || exit 1
        git read-tree --empty
        git read-tree --prefix=Ecommerce/ "$eco"
        git read-tree --prefix=Erp/ "$erp"
        git checkout-index -a -f >/dev/null 2>&1
        cat > composer.json <<'JSON'
{
    "name": "axelraboit/aurora-commerce",
    "description": "Commerce (Ecommerce + Erp) module for the Aurora platform.",
    "type": "symfony-bundle",
    "license": "proprietary",
    "require": {
        "php": ">=8.4",
        "axelraboit/aurora": "@dev"
    },
    "autoload": {
        "psr-4": {
            "Aurora\\Module\\Ecommerce\\": "Ecommerce/",
            "Aurora\\Module\\Erp\\": "Erp/"
        }
    },
    "extra": {
        "symfony": {
            "bundle": "Aurora\\Module\\Ecommerce\\AuroraEcommerceBundle"
        },
        "aurora": {
            "bundles": [
                "Aurora\\Module\\Ecommerce\\AuroraEcommerceBundle",
                "Aurora\\Module\\Erp\\AuroraErpBundle"
            ]
        }
    }
}
JSON
        git add -A
        git commit -q -m "aurora-commerce: Ecommerce + Erp (merge package snapshot)"
    )
    if git -C .commerce-build push -f "${ORG}/${repo}.git" "HEAD:${TARGET_BRANCH}" 2>/dev/null; then
        echo "   ok -> ${ORG}/${repo}.git"; ok+=("$repo")
    else
        echo "   !! push failed (repo created? access?)"; failed+=("$repo")
    fi
    git worktree remove --force .commerce-build >/dev/null 2>&1 || true
    git branch -D "$work" >/dev/null 2>&1 || true
}

for entry in "${SINGLE[@]}"; do
    repo="${entry%%:*}"; prefix="${entry##*:}"
    [ -n "$only" ] && [ "$only" != "$repo" ] && continue
    split_single "$repo" "$prefix"
done
if [ -z "$only" ] || [ "$only" = "aurora-commerce" ] || [ "$only" = "commerce" ]; then
    split_commerce
fi

echo
echo "Done. pushed: ${ok[*]:-none}"
[ "${#failed[@]}" -gt 0 ] && echo "FAILED: ${failed[*]}" && exit 1
exit 0

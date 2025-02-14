<!--
  - Copyright (c) Enalean, 2024 - present. All Rights Reserved.
  -
  - This file is a part of Tuleap.
  -
  - Tuleap is free software; you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation; either version 2 of the License, or
  - (at your option) any later version.
  -
  - Tuleap is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
    <div class="table-of-contents-container">
        <h1 class="tlp-pane-title">
            {{ $gettext("Table of contents") }}
        </h1>
        <ol>
            <li v-for="section in sections" v-bind:key="section.artifact.id">
                <span v-if="is_sections_loading" class="tlp-skeleton-text"></span>
                <a v-else v-bind:href="`#${section.artifact.id}`" class="section-title">
                    {{ section.title }}
                </a>
            </li>
        </ol>
    </div>
</template>

<script setup lang="ts">
import { useInjectSectionsStore } from "@/stores/useSectionsStore";
import { useGettext } from "vue3-gettext";

const { $gettext } = useGettext();

const { sections, is_sections_loading } = useInjectSectionsStore();
</script>

<style scoped lang="scss">
.table-of-contents-container {
    padding-top: var(--tlp-small-spacing);
}

h1 {
    margin: 0 0 var(--tlp-medium-spacing);
}

ol {
    height: var(--available-height-for-sidebar);
    overflow: hidden scroll;
}

li {
    margin: 0 0 var(--tlp-small-spacing);
}

.section-title {
    color: var(--tlp-dark-color);
    font-size: 0.875rem;
    font-weight: 600;
}

@media (max-width: 1024px) {
    .table-of-contents-container {
        padding-top: 0;
    }

    ol {
        height: fit-content;
    }
}
</style>

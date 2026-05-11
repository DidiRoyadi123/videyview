<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: Array,
});
</script>

<template>
    <div v-if="links.length > 3">
        <div class="flex flex-wrap -mb-1">
            <template v-for="(link, key) in links">
                <div v-if="link.url === null" :key="key" class="mr-1 mb-1 px-4 py-2 text-xs leading-4 text-gray-400 border border-gray-100 rounded-xl" v-html="link.label" />
                <Link v-else :key="'link-' + key" class="mr-1 mb-1 px-4 py-2 text-xs leading-4 border border-gray-100 rounded-xl hover:bg-white focus:border-indigo-500 focus:text-indigo-500 transition-all duration-200" :class="{ 'bg-white font-black border-indigo-500 text-indigo-600 shadow-sm': link.active }" :href="link.url" v-html="link.label" />
            </template>
        </div>
    </div>
</template>

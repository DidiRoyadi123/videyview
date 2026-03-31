<script setup>
import { Head, Link } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

defineProps({
    video: Object,
});
</script>

<template>
    <Head title="Premium Content Restricted" />

    <MainLayout>
        <div class="py-12">
            <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 p-8 rounded-lg shadow-xl text-center">
                    <div class="text-6xl mb-4">🔐</div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">Premium Content Restricted</h2>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        This video is for premium users only. Please login with a premium account to watch.
                    </p>
                    
                    <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg mb-6 text-sm text-left">
                        <p class="font-bold mb-2">How to get access?</p>
                        <p>1. Transfer the payment to our designated account.</p>
                        <p>2. Send the proof of payment to our Telegram admin: <a href="https://t.me/Mandorbuah" target="_blank" class="text-blue-500 underline">@Mandorbuah</a></p>
                        <p>3. Your account details will be sent to you shortly.</p>
                    </div>

                    <div class="flex flex-col gap-3">
                        <Link :href="route('login')" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-blue-700 transition">
                            Login Here
                        </Link>
                        <Link :href="route('home')" class="text-gray-500 underline text-sm">
                            Back to Home
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

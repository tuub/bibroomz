<template>
    <ul class="list-disc">
        <li><Link href="/admin/happenings" class="underline">Happenings</Link></li>
        <li><Link href="/admin/institutions" class="underline">Institutions</Link></li>
        <li><Link href="/admin/resources" class="underline">Resources</Link></li>
        <li><Link href="/admin/users" class="underline">Users</Link></li>
        <li><Link href="/admin/stats" class="underline">Stats</Link></li>
    </ul>
</template>

<script setup>
import { Link } from "@inertiajs/vue3";
</script>

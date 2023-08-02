<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, Head } from '@inertiajs/vue3'
import { onMounted } from 'vue';
import { useToast } from "vue-toastification";

const toast = useToast();
onMounted(() => {
    console.log('on mounted run')
    // toast.success("My toast content");
})
defineProps({ users: Array })

</script>

<template>
    <AppLayout title="Abandoned Carts">
   

      <div v-if="$page.props.flash.message" class="alert">
        {{ $page.props.flash.message }}
      </div>

        <div v-for="user in users" :key="user.id">
      <Link :href="`/users/${user.id}`">
        {{ user.name }}
      </Link>
      <div>{{ user.email }}</div>
    </div>
    </AppLayout>
</template>

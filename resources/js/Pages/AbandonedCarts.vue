<template>
  <AppLayout title="Abandoned Carts">
    <section class="bg-white">
      <div class="mx-auto max-w-screen-xl px-4 py-12 sm:px-6 md:py-16 lg:px-8">
        <div class="mx-auto max-w-3xl text-center">
          <h2 class="text-3xl font-bold text-gray-900 sm:text-4xl">
            Trusted by eCommerce Businesses
          </h2>

          <p class="mt-4 text-gray-500 sm:text-xl">
            Lorem ipsum dolor sit amet consectetur adipisicing elit. Ratione dolores
            laborum labore provident impedit esse recusandae facere libero harum
            sequi.
          </p>
        </div>

        <div class="mt-8 sm:mt-12">
          <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="flex flex-col rounded-lg bg-blue-100 px-4 py-8 text-center">
              <dt class="order-last text-lg font-medium text-gray-500">
                Total Abandoned Carts
              </dt>

              <dd class="text-4xl font-extrabold text-blue-600 md:text-5xl">
                {{ countAbandonedCheckouts }}
              </dd>
            </div>

            <div class="flex flex-col rounded-lg bg-blue-100 px-4 py-8 text-center">
              <dt class="order-last text-lg font-medium text-gray-500">
                Total Value of Abandoned Carts
              </dt>

              <dd class="text-4xl font-extrabold text-blue-600 md:text-5xl">
                {{ totalSubtotalPrice }}
              </dd>
            </div>

            <div class="flex flex-col rounded-lg bg-blue-100 px-4 py-8 text-center">
              <dt class="order-last text-lg font-medium text-gray-500">
                Total Carts Recovered Till Now
              </dt>

              <dd class="text-4xl font-extrabold text-blue-600 md:text-5xl">
                {{ countCompletedCheckouts }}
              </dd>
            </div>
          </dl>
        </div>
      </div>
    </section>
    <div class="container mx-auto py-8 px-4">
      <div class="card">
        <DataTable :value="abandoned_checkouts" paginator :rows="5" :rowsPerPageOptions="[5, 10, 20, 50]"
          tableStyle="min-width: 50rem">
          <template #header>
            <div class="flex justify-between items-center gap-2">
              <span class="text-xl text-900 font-bold">Abandoned Carts</span>
              <Button @click="refresh" icon="pi pi-refresh" rounded raised />
            </div>
          </template>
          <Column field="id" header="Checkout URL">
            <template #body="slotProps">
              <a style="color: blue" :href="slotProps.data.abandoned_checkout_url" target="_blank">{{ slotProps.data.id
              }}</a>
            </template>
          </Column>
          <Column field="customer.first_name" header="Placed By"></Column>
          <Column field="customer.created_at" header="Date">
            <template #body="slotProps">
              {{ formatDate(slotProps.data.customer.created_at) }}
            </template>
          </Column>
          <Column field="customer.email" header="Email"></Column>
          <Column field="subtotal_price" header="Order Price"></Column>
          <Column header="Status">
            <template #body="slotProps">
              <Tag :value="slotProps.data.completed_at ? 'Recovered' : 'Not Recovered'"
                :severity="slotProps.data.completed_at ? 'success' : 'danger'" />
            </template>
          </Column>
        </DataTable>
      </div>
    </div>


  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, Head, router } from '@inertiajs/vue3'
import { onMounted, ref, computed } from 'vue';
import { useToast } from "vue-toastification";
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import { formatDistanceToNow } from 'date-fns'

const toast = useToast();
const props = defineProps({ abandoned_checkouts: Array })
const formatDate = (date) => {
  return formatDistanceToNow(new Date(date), { addSuffix: true })
}
const refresh = () => {
  router.reload({ only: ['abandoned_checkouts'] })
}
const countAbandonedCheckouts = computed(() => {
  return (props.abandoned_checkouts.length)
})
const totalSubtotalPrice = computed(() => {
  let total = props.abandoned_checkouts.reduce((total, checkout) => {
    return total + parseFloat(checkout.subtotal_price);
  }, 0);

  // Check if there are any items in the array
  if (props.abandoned_checkouts.length > 0) {
    // Get the currency symbol from the first item
    let currencySymbol = props.abandoned_checkouts[0].presentment_currency;

    // Append the currency symbol to the total and return
    return total.toFixed(2) + ' ' + currencySymbol;
  } else {
    return '0';
  }
})
const countCompletedCheckouts = computed(() => {
  return props.abandoned_checkouts.reduce((count, checkout) => {
    // If completed_at is not null, increment the count
    if (checkout.completed_at !== null) {
      return count + 1;
    } else {
      return count;
    }
  }, 0);
});
onMounted(() => {
  console.log(totalSubtotalPrice.value);
})
// const checkouts = ref([abandoned_checkouts);





</script>
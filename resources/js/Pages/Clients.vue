<script setup>
import { ref, computed } from 'vue';
import MainLayout from '../Layouts/MainLayout.vue';

const props = defineProps({
    clients: Array,
});

const searchQuery = ref('');

const filteredClients = computed(() => {
    if (!searchQuery.value) return props.clients;
    const query = searchQuery.value.toLowerCase();
    return props.clients.filter(c => 
        (c.nom && c.nom.toLowerCase().includes(query)) ||
        (c.clientcode && String(c.clientcode).toLowerCase().includes(query)) ||
        (String(c.clientid).toLowerCase().includes(query))
    );
});

const formatCurrency = (val) => {
    return new Intl.NumberFormat('fr-TN', { minimumFractionDigits: 3 }).format(val);
};
</script>

<template>
    <MainLayout>
        <Head title="Clients" />
        
        <div class="p-4 sm:p-6 md:p-8 lg:p-12 pb-8 safe-bottom">
            <header class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8 md:mb-12">
                <div>
                    <h2 class="text-2xl md:text-3xl font-extrabold text-[#1b1b18] tracking-tight mb-1">Tableau de bord Clients</h2>
                    <p class="text-sm md:text-base text-[#706f6c] font-medium">Gestion des comptes clients</p>
                </div>
                
                <div class="flex items-center gap-4 bg-white border border-[#e3e3e0] p-1.5 rounded-2xl shadow-sm self-start md:self-auto">
                    <div class="px-4 py-2 bg-[#f8f8f7] rounded-xl text-sm font-bold text-[#1b1b18]">
                        {{ clients.length }} Clients
                    </div>
                </div>
            </header>

            <!-- Stats Bar -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6 mb-8 md:mb-12">
                <div class="bg-white p-6 rounded-[2rem] border border-[#e3e3e0] shadow-sm flex items-center gap-6">
                    <div class="w-14 h-14 rounded-2xl bg-brand-gold/10 flex items-center justify-center text-brand-gold shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-[#706f6c] uppercase tracking-wider mb-1">Total Crédit Clients</p>
                        <p class="text-xl font-black text-brand-gold tabular-nums">{{ formatCurrency(clients.reduce((acc, c) => acc + (parseFloat(c.solde) > 0 ? parseFloat(c.solde) : 0), 0)) }} TND</p>
                    </div>
                </div>
            </div>

            <!-- Main Table -->
            <div class="bg-white rounded-[1.5rem] md:rounded-[2rem] border border-[#e3e3e0] shadow-sm overflow-hidden">
                <div class="p-6 border-b border-[#f0f0f0] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <h3 class="font-bold text-[#1b1b18]">Liste des Clients</h3>
                    <div class="relative w-full sm:w-auto">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-[#706f6c]" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        <input 
                            v-model="searchQuery"
                            type="text" 
                            placeholder="Rechercher un client (nom, code...)" 
                            class="w-full bg-[#f8f8f7] border-transparent rounded-xl py-2 pl-10 pr-4 text-sm focus:bg-white focus:border-brand-gold focus:ring-0 transition-all outline-none sm:w-80" 
                        />
                    </div>
                </div>
                
                <div class="overflow-x-auto table-scroll">
                    <table class="w-full text-left min-w-[500px]">
                        <thead>
                            <tr class="text-[10px] font-bold text-[#706f6c] uppercase tracking-widest bg-[#fdfdfc] border-b border-[#f0f0f0]">
                                <th class="px-4 md:px-8 py-5 hidden md:table-cell">Code</th>
                                <th class="px-4 md:px-8 py-5">Identité</th>
                                <th class="px-6 md:px-8 py-5 hidden lg:table-cell">Localisation</th>
                                <th class="px-4 md:px-8 py-5 text-right">Solde</th>
                                <th class="px-4 md:px-8 py-5 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#f0f0f0]">
                            <tr v-if="filteredClients.length === 0">
                                <td colspan="5" class="px-8 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-12 h-12 bg-[#f8f8f7] rounded-full flex items-center justify-center text-[#a0a09e] mb-3">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                                        </div>
                                        <p class="text-[#706f6c] font-black text-sm">Aucun client trouvé pour "{{ searchQuery }}"</p>
                                    </div>
                                </td>
                            </tr>
                            <tr v-for="c in filteredClients" :key="c.clientid" class="hover:bg-[#fcfcfb] group transition-colors">
                                <td class="px-4 md:px-8 py-5 md:py-6 hidden md:table-cell">
                                    <span class="px-2 py-1 bg-[#f8f8f7] rounded-md font-mono text-[10px] font-bold text-[#1b1b18]">{{ c.clientcode ?? c.clientid }}</span>
                                </td>
                                <td class="px-4 md:px-8 py-5 md:py-6">
                                    <div class="flex flex-col min-w-0">
                                        <span class="font-bold text-[#1b1b18] group-hover:text-brand-gold transition-colors leading-tight text-sm md:text-base truncate max-w-[120px] sm:max-w-none">{{ c.nom }}</span>
                                        <span class="text-[9px] md:text-[10px] text-[#706f6c] font-bold uppercase mt-1">{{ c.famille || 'SANS FAMILLE' }}</span>
                                    </div>
                                </td>
                                <td class="px-6 md:px-8 py-5 md:py-6 hidden lg:table-cell">
                                    <div class="flex flex-col max-w-[200px] overflow-hidden">
                                        <span class="text-sm text-[#1b1b18] truncate">{{ c.adressefacturation || c.ville || '—' }}</span>
                                        <span class="text-xs text-[#706f6c] font-medium">{{ c.tel || 'Aucun tel' }}</span>
                                    </div>
                                </td>
                                <td class="px-4 md:px-8 py-5 md:py-6 text-right">
                                    <span :class="[parseFloat(c.solde) > 0 ? 'text-brand-gold' : parseFloat(c.solde) < 0 ? 'text-[#10b981]' : 'text-[#f59e0b]', 'font-bold text-sm md:text-lg tabular-nums tracking-tighter']">
                                        {{ formatCurrency(c.solde) }}
                                    </span>
                                </td>
                                <td class="px-4 md:px-8 py-5 md:py-6 text-right">
                                    <Link :href="`/client/${c.clientid}/releve`" class="inline-flex items-center justify-center w-8 h-8 md:w-10 md:h-10 rounded-xl bg-[#f8f8f7] text-[#1b1b18] hover:bg-brand-gold hover:text-white transition-all shadow-sm">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

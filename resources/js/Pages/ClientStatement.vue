<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import MainLayout from '../Layouts/MainLayout.vue';

const props = defineProps({
    client: Object,
    movements: Array,
    summary: Object,
});

// Pre-calculate running balances for better performance in Vue
const movementsWithBalances = computed(() => {
    let running = parseFloat(props.summary.solde_depart || 0);
    return props.movements.map(m => {
        const credit = parseFloat(m.credit || 0);
        const debit = parseFloat(m.debit || 0);
        running = running + debit - credit; // For clients: Facture = Debit (+), Règlement = Credit (-)
        return { ...m, cumulative: running };
    });
});

const formatCurrency = (val) => {
    return new Intl.NumberFormat('fr-TN', { minimumFractionDigits: 3 }).format(val);
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR');
};

const printPage = () => {
    window.print();
};
</script>

<template>
    <MainLayout>
        <Head :title="'Relevé ' + client.nom" />
        
        <div class="p-4 sm:p-6 md:p-8 lg:p-12 pb-8 print:p-0 safe-bottom">
            <header class="flex flex-col xl:flex-row xl:items-end justify-between gap-8 mb-8 md:mb-12 print:mb-8">
                <div class="space-y-4">
                    <Link href="/clients" class="inline-flex items-center gap-2 text-[#706f6c] text-[10px] font-bold uppercase tracking-widest hover:text-brand-gold transition-colors print:hidden">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                        Retour aux Clients
                    </Link>
                    <div>
                        <h2 class="text-3xl md:text-4xl font-black text-[#1b1b18] tracking-tight mb-2">Relevé de Compte Client</h2>
                        <div class="flex flex-wrap items-center gap-4 md:gap-6">
                            <span class="flex items-center gap-2">
                                <span class="text-[9px] md:text-[10px] font-bold text-[#706f6c] uppercase tracking-widest">Client</span>
                                <span class="font-black text-[#1b1b18] text-sm md:text-base">{{ client.nom }}</span>
                            </span>
                            <span v-if="client.clientcode" class="flex items-center gap-2">
                                <span class="text-[9px] md:text-[10px] font-bold text-[#706f6c] uppercase tracking-widest">Code</span>
                                <span class="px-2 py-0.5 bg-[#f8f8f7] border border-[#e3e3e0] rounded-md font-mono text-xs md:text-sm uppercase leading-none">{{ client.clientcode }}</span>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3 print:hidden self-start xl:self-auto">
                    <button @click="printPage" class="bg-brand-charcoal text-white px-5 md:px-6 py-2.5 md:py-3 rounded-2xl font-bold flex items-center gap-2 shadow-lg shadow-brand-charcoal/10 hover:shadow-brand-charcoal/20 transition-all active:scale-95 text-sm md:text-base">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                        <span class="hidden md:inline">Imprimer</span>
                    </button>
                </div>
            </header>

            <!-- Summary Bar -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-7 gap-3 mb-8 md:mb-12 print:mb-8 print:gap-2">
                <div v-for="(val, key) in {
                    'Départ': summary.solde_depart,
                    'Factures': summary.factures,
                    'Bons Livraison': summary.bons_sortie,
                    'Règlements': summary.reglements,
                    'Bons Retour': summary.bons_entree,
                    'Avoirs': summary.avoirs,
                    'FINAL': summary.solde_final
                }" :key="key" class="bg-white p-3 md:p-4 rounded-2xl border border-[#e3e3e0] flex flex-col items-center justify-center text-center" :class="{ 'hidden sm:flex': key !== 'FINAL' }">
                    <span class="text-[8px] md:text-[9px] font-bold text-[#706f6c] uppercase tracking-widest mb-1">{{ key }}</span>
                    <span :class="['font-black text-xs md:text-sm tabular-nums', key === 'FINAL' ? (parseFloat(val) > 0 ? 'text-brand-gold' : parseFloat(val) < 0 ? 'text-[#10b981]' : 'text-[#f59e0b]') : 'text-brand-charcoal']">
                        {{ formatCurrency(val) }}
                    </span>
                </div>
            </div>

            <!-- Ledger Table -->
            <div class="bg-white rounded-[1.5rem] md:rounded-[2rem] border border-[#e3e3e0] shadow-sm overflow-hidden print:border-none print:shadow-none">
                <div class="overflow-x-auto table-scroll">
                    <table class="w-full text-left min-w-[520px]">
                        <thead>
                            <tr class="text-[10px] font-bold text-[#706f6c] uppercase tracking-widest bg-[#fdfdfc] border-b border-[#f0f0f0]">
                                <th class="px-4 md:px-8 py-5 hidden md:table-cell">Date</th>
                                <th class="px-4 md:px-8 py-5 hidden sm:table-cell">Opération</th>
                                <th class="px-4 md:px-8 py-5">Pièce</th>
                                <th class="px-4 md:px-8 py-5 text-right hidden lg:table-cell">Débit (Dû)</th>
                                <th class="px-4 md:px-8 py-5 text-right hidden lg:table-cell">Crédit (Payé)</th>
                                <th class="px-4 md:px-8 py-5 text-right">Solde</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#f0f0f0]">
                            <!-- Opening Balance -->
                            <tr class="bg-[#fcfcfb]">
                                <td class="px-4 md:px-8 py-5 text-[10px] md:text-sm text-[#706f6c] font-bold hidden md:table-cell">—</td>
                                <td class="px-4 md:px-8 py-5 font-black text-[#1b1b18] text-[9px] md:text-xs hidden sm:table-cell uppercase">INITIAL</td>
                                <td class="px-4 md:px-8 py-5 text-[10px] md:text-sm text-[#706f6c]">SOLDE INITIAL</td>
                                <td class="px-4 md:px-8 py-5 text-right text-sm hidden lg:table-cell">—</td>
                                <td class="px-4 md:px-8 py-5 text-right text-sm hidden lg:table-cell">—</td>
                                <td class="px-4 md:px-8 py-5 text-right font-black text-[#1b1b18] tabular-nums text-sm md:text-base">
                                    {{ formatCurrency(summary.solde_depart) }}
                                </td>
                            </tr>
                            
                            <!-- List of movements -->
                            <template v-for="(m, index) in movementsWithBalances" :key="index">
                                <tr :class="{'bg-[#fcfcfb]/50': !!m.details}">
                                    <td class="px-4 md:px-8 py-5 md:py-6 text-[10px] md:text-sm font-medium text-[#706f6c] hidden md:table-cell">{{ formatDate(m.date) }}</td>
                                    <td class="px-4 md:px-8 py-5 md:py-6 hidden sm:table-cell">
                                        <span class="px-2 py-1 rounded-lg bg-[#f8f8f7] text-[9px] md:text-[10px] font-black text-[#1b1b18] uppercase tracking-wider border border-[#e3e3e0] truncate max-w-[85px] block text-center">{{ m.libelle }}</span>
                                    </td>
                                    <td class="px-4 md:px-8 py-5 md:py-6">
                                        <div class="flex flex-col">
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-[#1b1b18] text-[10px] md:text-base leading-none">#{{ m.numero }}</span>
                                                <span class="md:hidden text-[9px] text-[#706f6c]">{{ formatDate(m.date) }}</span>
                                            </div>
                                            <span class="sm:hidden text-[8px] font-bold text-[#706f6c] uppercase mt-1">{{ m.libelle }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 md:px-8 py-5 md:py-6 text-right tabular-nums text-sm font-bold text-[#1b1b18] hidden lg:table-cell">{{ m.debit > 0 ? formatCurrency(m.debit) : '—' }}</td>
                                    <td class="px-4 md:px-8 py-5 md:py-6 text-right tabular-nums text-sm font-bold text-[#10b981] hidden lg:table-cell">{{ m.credit > 0 ? formatCurrency(m.credit) : '—' }}</td>
                                    <td class="px-4 md:px-8 py-5 md:py-6 text-right font-black tabular-nums tracking-tighter text-xs md:text-lg">
                                        <div class="flex flex-col items-end" :class="[
                                            parseFloat(m.cumulative) > 0 ? 'text-brand-gold' : 
                                            parseFloat(m.cumulative) < 0 ? 'text-[#10b981]' : 
                                            'text-[#f59e0b]'
                                        ]">
                                            <span>{{ formatCurrency(m.cumulative) }}</span>
                                            <span v-if="m.debit > 0" class="lg:hidden text-[9px] font-bold mt-0.5 text-[#1b1b18]">+ {{ formatCurrency(m.debit) }}</span>
                                            <span v-if="m.credit > 0" class="lg:hidden text-[9px] font-bold mt-0.5 text-[#10b981]">- {{ formatCurrency(m.credit) }}</span>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<style>
@media print {
    body { background: white !important; }
    main { padding: 0 !important; margin-left: 0 !important; }
    .bg-white { border: none !important; box-shadow: none !important; }
    .divide-y > * { border-bottom-width: 1px !important; border-color: #eee !important; }
}
</style>

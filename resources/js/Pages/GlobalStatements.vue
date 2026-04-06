<script setup>
import { ref, computed } from 'vue';
import MainLayout from '../Layouts/MainLayout.vue';

const props = defineProps({
    statements: Array,
});

const searchQuery = ref('');

const filteredStatements = computed(() => {
    if (!searchQuery.value) return props.statements;
    
    const query = searchQuery.value.toLowerCase();
    return props.statements.filter(s => 
        (s.fournisseur.nom && s.fournisseur.nom.toLowerCase().includes(query)) ||
        (s.fournisseur.fournisseurcode && s.fournisseur.fournisseurcode.toLowerCase().includes(query))
    );
});

const formatCurrency = (val) => {
    return new Intl.NumberFormat('fr-TN', { minimumFractionDigits: 3 }).format(val);
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR');
};

const exportExcel = () => {
    // Add BOM for UTF-8 to ensure Excel reads special characters correctly
    let csvContent = "data:text/csv;charset=utf-8,\uFEFF";
    
    // Header
    csvContent += "Fournisseur;Date;Opération;Pièce;Débit;Crédit;Solde\n";

    props.statements.forEach(statement => {
        let nom = statement.fournisseur.nom ? statement.fournisseur.nom.replace(/"/g, '""') : '';
        
        // Initial Balance
        csvContent += `"${nom}";;;"SOLDE INITIAL";;;${statement.summary.solde_depart}\n`;
        
        // Movements
        statement.movements.forEach(m => {
            const date = formatDate(m.date);
            const debit = parseFloat(m.debit) > 0 ? parseFloat(m.debit) : '';
            const credit = parseFloat(m.credit) > 0 ? parseFloat(m.credit) : '';
            const libelle = m.libelle ? m.libelle.replace(/"/g, '""') : '';
            const numero = m.numero ? String(m.numero).replace(/"/g, '""') : '';
            
            csvContent += `"${nom}";"${date}";"${libelle}";"${numero}";"${debit}";"${credit}";"${m.solde_cumule}"\n`;
        });
        
        // Final Balance
        csvContent += `"${nom}";;;"SOLDE FINAL";;;${statement.summary.solde_final}\n`;
        csvContent += ";;;;;;\n"; // Empty row separator
    });

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "releves_fournisseurs_globaux.csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

const exportSingleExcel = (statement) => {
    let csvContent = "data:text/csv;charset=utf-8,\uFEFF";
    csvContent += "Date;Opération;Pièce;Débit;Crédit;Solde\n";
    
    let nom = statement.fournisseur.nom ? statement.fournisseur.nom.replace(/"/g, '""') : '';
    csvContent += `;;"SOLDE INITIAL";;;${statement.summary.solde_depart}\n`;
    
    statement.movements.forEach(m => {
        const date = formatDate(m.date);
        const debit = parseFloat(m.debit) > 0 ? decFormat(m.debit) : '';
        const credit = parseFloat(m.credit) > 0 ? decFormat(m.credit) : '';
        const libelle = m.libelle ? m.libelle.replace(/"/g, '""') : '';
        const numero = m.numero ? String(m.numero).replace(/"/g, '""') : '';
        const solde = decFormat(m.solde_cumule);
        
        csvContent += `"${date}";"${libelle}";"${numero}";"${debit}";"${credit}";"${solde}"\n`;
    });
    
    csvContent += `;;"SOLDE FINAL";;;${decFormat(statement.summary.solde_final)}\n`;
    
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    const safeName = nom.replace(/[^a-z0-9]/gi, '_').toLowerCase();
    link.setAttribute("download", `releve_${safeName}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

// Helper function to force comma separator in Excel if localization requires it
const decFormat = (val) => {
    return parseFloat(val).toFixed(3).replace('.', ',');
};

const expandedDocs = ref([]);

const toggleDoc = (docId, typeSlug) => {
    if (typeSlug === 'facture' || typeSlug === 'avoir') return; // Not handled yet
    
    const id = `${typeSlug}_${docId}`;
    if (expandedDocs.value.includes(id)) {
        expandedDocs.value = expandedDocs.value.filter(i => i !== id);
    } else {
        expandedDocs.value.push(id);
    }
};

const isExpanded = (docId, typeSlug) => {
    return expandedDocs.value.includes(`${typeSlug}_${docId}`);
};
</script>

<template>
    <MainLayout>
        <Head title="Relevés Globaux Fournisseurs" />
        
        <div class="p-4 sm:p-6 md:p-8 lg:p-12 pb-8 print:p-0 safe-bottom">
            <header class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-8 md:mb-12 print:mb-8">
                <div class="space-y-4">
                    <Link href="/dashboard" class="inline-flex items-center gap-2 text-[#706f6c] text-[10px] font-bold uppercase tracking-widest hover:text-brand-gold transition-colors print:hidden">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                        Retour
                    </Link>
                    <div>
                        <h2 class="text-3xl md:text-4xl font-black text-[#1b1b18] tracking-tight mb-2">Relevés Fournisseurs</h2>
                        <p class="text-[12px] md:text-sm font-bold text-[#706f6c] uppercase tracking-widest">Liste détaillée de tous les mouvements regroupés</p>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-center gap-3 print:hidden self-start md:self-auto w-full md:w-auto mt-4 md:mt-0">
                    <div class="relative w-full sm:w-64 md:w-80">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#706f6c]">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        </span>
                        <input 
                            v-model="searchQuery"
                            type="text" 
                            placeholder="Rechercher un fournisseur..."
                            class="block w-full pl-10 pr-3 py-2.5 bg-white border border-[#e3e3e0] rounded-2xl text-sm font-bold text-[#1b1b18] placeholder-[#a0a09e] focus:outline-none focus:ring-2 focus:ring-brand-gold/20 focus:border-brand-gold transition-all shadow-sm"
                        >
                    </div>
                    
                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <button @click="exportExcel" class="flex-1 sm:flex-none bg-[#10b981] text-white px-5 py-2.5 rounded-2xl font-bold flex items-center justify-center gap-2 shadow-lg shadow-[#10b981]/20 hover:shadow-[#10b981]/40 transition-all active:scale-95 text-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                            <span class="md:hidden lg:inline">Exporter Excel</span>
                        </button>
                        <button onclick="window.print()" class="flex-1 sm:flex-none bg-brand-charcoal text-white px-5 py-2.5 rounded-2xl font-bold flex items-center justify-center gap-2 shadow-lg shadow-brand-charcoal/10 hover:shadow-brand-charcoal/20 transition-all active:scale-95 text-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                            <span class="md:hidden lg:inline">Imprimer</span>
                        </button>
                    </div>
                </div>
            </header>

            <div v-if="filteredStatements.length === 0" class="bg-white p-12 rounded-[2rem] border border-[#e3e3e0] text-center shadow-sm">
                <div class="w-20 h-20 bg-[#f8f8f7] rounded-full flex items-center justify-center mx-auto mb-4 text-[#a0a09e]">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </div>
                <p class="text-[#706f6c] font-black text-lg">Aucun fournisseur trouvé pour "{{ searchQuery }}"</p>
                <p class="text-[#a0a09e] text-sm mt-1">Vérifiez l'orthographe ou essayez un autre nom.</p>
                <button @click="searchQuery = ''" class="mt-6 text-brand-gold font-bold text-sm hover:underline cursor-pointer">Effacer la recherche</button>
            </div>

            <div v-for="statement in filteredStatements" :key="statement.fournisseur.fournisseurid" class="mb-12 bg-white rounded-[1.5rem] md:rounded-[2rem] border border-[#e3e3e0] shadow-sm overflow-hidden print:border-none print:shadow-none print:mb-8">
                <div class="p-6 md:p-8 border-b border-[#f0f0f0] bg-[#fdfdfc] flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-4">
                            <h3 class="text-xl md:text-2xl font-black text-[#1b1b18]">{{ statement.fournisseur.nom }}</h3>
                            <button @click="exportSingleExcel(statement)" class="text-[#10b981] hover:text-white hover:bg-[#10b981] px-3 py-1.5 rounded-lg text-xs font-bold flex items-center gap-1.5 transition-all print:hidden border border-[#10b981]/30 hover:shadow-md hover:shadow-[#10b981]/20">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                                Excel
                            </button>
                        </div>
                        <p v-if="statement.fournisseur.fournisseurcode" class="text-xs font-mono text-[#706f6c] mt-1">{{ statement.fournisseur.fournisseurcode }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[9px] font-bold text-[#706f6c] uppercase tracking-widest mb-1">Solde Final</p>
                        <p :class="['text-xl md:text-2xl font-black tabular-nums', parseFloat(statement.summary.solde_final) > 0 ? 'text-brand-gold' : parseFloat(statement.summary.solde_final) < 0 ? 'text-[#10b981]' : 'text-[#f59e0b]']">
                            {{ formatCurrency(statement.summary.solde_final) }}
                        </p>
                    </div>
                </div>

                <div class="overflow-x-auto table-scroll">
                    <table class="w-full text-left min-w-[520px]">
                        <thead>
                            <tr class="text-[10px] font-bold text-[#706f6c] uppercase tracking-widest bg-white border-b border-[#f0f0f0]">
                                <th class="px-4 md:px-8 py-4 hidden md:table-cell">Date</th>
                                <th class="px-4 md:px-8 py-4 hidden sm:table-cell">Opération</th>
                                <th class="px-4 md:px-8 py-4">Pièce</th>
                                <th class="px-4 md:px-8 py-4 text-right hidden lg:table-cell">Débit</th>
                                <th class="px-4 md:px-8 py-4 text-right hidden lg:table-cell">Crédit</th>
                                <th class="px-4 md:px-8 py-4 text-right">Solde</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#f0f0f0]">
                            <!-- Solde Initial -->
                            <tr class="bg-[#fcfcfb]">
                                <td class="px-4 md:px-8 py-4 text-[10px] md:text-sm text-[#706f6c] font-bold hidden md:table-cell">—</td>
                                <td class="px-4 md:px-8 py-4 font-black text-[#1b1b18] text-[9px] md:text-xs hidden sm:table-cell uppercase">INITIAL</td>
                                <td class="px-4 md:px-8 py-4 text-[10px] md:text-sm text-[#706f6c] font-bold">SOLDE INITIAL</td>
                                <td class="px-4 md:px-8 py-4 text-right text-sm hidden lg:table-cell">—</td>
                                <td class="px-4 md:px-8 py-4 text-right text-sm hidden lg:table-cell">—</td>
                                <td class="px-4 md:px-8 py-4 text-right font-black text-[#1b1b18] tabular-nums text-sm md:text-base">
                                    {{ formatCurrency(statement.summary.solde_depart) }}
                                </td>
                            </tr>
                                 <!-- Mouvements -->
                            <template v-for="(m, index) in statement.movements" :key="index">
                                <tr 
                                    @click="toggleDoc(m.doc_id, m.type_slug)"
                                    :class="[
                                        'transition-colors',
                                        ['bon-entree', 'bon-sortie', 'reglement'].includes(m.type_slug) ? 'cursor-pointer hover:bg-[#fcfcfb]' : 'hover:bg-[#fafafa]',
                                        isExpanded(m.doc_id, m.type_slug) ? 'bg-[#f8f8f7]' : ''
                                    ]"
                                >
                                    <td class="px-4 md:px-8 py-4 text-[10px] md:text-sm font-medium text-[#706f6c] hidden md:table-cell">{{ formatDate(m.date) }}</td>
                                    <td class="px-4 md:px-8 py-4 hidden sm:table-cell">
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-1 rounded-lg bg-[#f8f8f7] text-[9px] font-black text-[#1b1b18] uppercase tracking-wider border border-[#e3e3e0] block w-max max-w-[100px] truncate text-center">{{ m.libelle }}</span>
                                            <svg v-if="['bon-entree', 'bon-sortie', 'reglement'].includes(m.type_slug)" 
                                                :class="['text-brand-gold transition-transform shrink-0', isExpanded(m.doc_id, m.type_slug) ? 'rotate-180' : '']"
                                                xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                                        </div>
                                    </td>
                                    <td class="px-4 md:px-8 py-4">
                                        <div class="flex flex-col">
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-[#1b1b18] text-xs md:text-sm leading-none">#{{ m.numero }}</span>
                                                <span class="md:hidden text-[9px] text-[#706f6c]">{{ formatDate(m.date) }}</span>
                                            </div>
                                            <span class="sm:hidden text-[8px] font-bold text-[#706f6c] uppercase mt-1">{{ m.libelle }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 md:px-8 py-4 text-right tabular-nums text-sm font-bold text-[#1b1b18] hidden lg:table-cell">{{ m.debit > 0 ? formatCurrency(m.debit) : '—' }}</td>
                                    <td class="px-4 md:px-8 py-4 text-right tabular-nums text-sm font-bold text-brand-gold hidden lg:table-cell">{{ m.credit > 0 ? formatCurrency(m.credit) : '—' }}</td>
                                    <td class="px-4 md:px-8 py-4 text-right font-black tabular-nums tracking-tighter text-sm md:text-base">
                                        <div class="flex flex-col items-end" :class="[
                                            parseFloat(m.solde_cumule) > 0 ? 'text-brand-gold' : 
                                            parseFloat(m.solde_cumule) < 0 ? 'text-[#10b981]' : 
                                            'text-[#f59e0b]'
                                        ]">
                                            <span>{{ formatCurrency(m.solde_cumule) }}</span>
                                            <span v-if="m.debit > 0" class="lg:hidden text-[9px] font-bold mt-0.5 text-[#1b1b18]">- {{ formatCurrency(m.debit) }}</span>
                                            <span v-if="m.credit > 0" class="lg:hidden text-[9px] font-bold mt-0.5 text-brand-gold">+ {{ formatCurrency(m.credit) }}</span>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Details Row -->
                                <tr v-if="isExpanded(m.doc_id, m.type_slug)" class="bg-[#fcfcfb]">
                                    <td colspan="6" class="p-0">
                                        <div class="border-l-4 border-brand-gold bg-white m-3 md:m-4 md:ml-8 p-4 md:p-6 rounded-2xl shadow-sm border border-[#e3e3e0]">
                                            <!-- Details for Bons -->
                                            <template v-if="['bon-entree', 'bon-sortie'].includes(m.type_slug)">
                                                <div class="flex flex-col sm:flex-row justify-between items-start gap-4 mb-4">
                                                    <div>
                                                        <h4 class="font-black text-base text-[#1b1b18] mb-1">{{ m.libelle }} #{{ m.numero }}</h4>
                                                        <p class="text-[9px] font-bold text-[#706f6c] uppercase tracking-widest">Réf Interne: {{ m.header?.numerointerne || '—' }}</p>
                                                    </div>
                                                    <div class="sm:text-right">
                                                        <p class="text-[8px] font-bold text-[#706f6c] uppercase tracking-widest">Total Document</p>
                                                        <p class="text-base font-black text-brand-gold">{{ formatCurrency(m.header?.netapayer) }} TND</p>
                                                    </div>
                                                </div>

                                                <div class="overflow-x-auto table-scroll">
                                                    <table class="w-full text-[10px] min-w-[320px]">
                                                        <thead>
                                                            <tr class="border-b border-[#f0f0f0] text-[#706f6c] font-black uppercase tracking-widest">
                                                                <th class="py-2 text-left">Référence</th>
                                                                <th class="py-2 text-left">Désignation</th>
                                                                <th class="py-2 text-right">Qté</th>
                                                                <th class="py-2 text-right">TTC</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr v-for="d in m.details" :key="d.produitid" class="border-b border-[#f8f8f7] last:border-0">
                                                                <td class="py-2 font-mono text-[#706f6c]">{{ d.produitcode }}</td>
                                                                <td class="py-2 font-bold text-[#1b1b18]">{{ d.produitlibelle }}</td>
                                                                <td class="py-2 text-right font-bold">{{ d.qte }}</td>
                                                                <td class="py-2 text-right font-black text-[#1b1b18]">{{ formatCurrency(d.totalttc) }}</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </template>

                                            <!-- Details for Reglements -->
                                            <template v-else-if="m.type_slug === 'reglement'">
                                                <div class="space-y-4">
                                                    <div class="flex items-center gap-3">
                                                        <div class="w-10 h-10 rounded-full bg-[#10b981]/10 flex items-center justify-center text-[#10b981]">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                                                        </div>
                                                        <div>
                                                            <h4 class="font-black text-base text-[#1b1b18]">Détails du Règlement</h4>
                                                            <p class="text-[9px] font-bold text-[#706f6c] uppercase tracking-widest">Opération de trésorerie</p>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 bg-[#f8f8f7] p-4 rounded-xl border border-[#e3e3e0]">
                                                        <div>
                                                            <p class="text-[8px] font-bold text-[#706f6c] uppercase tracking-widest mb-1">Mode de paiement</p>
                                                            <p class="font-bold text-[#1b1b18]">{{ m.extra?.mode_libelle || 'Non spécifié' }}</p>
                                                        </div>
                                                        <div>
                                                            <p class="text-[8px] font-bold text-[#706f6c] uppercase tracking-widest mb-1">Information / Raison</p>
                                                            <p class="font-bold text-[#1b1b18]">{{ m.extra?.raisonreglement || m.extra?.observation || '—' }}</p>
                                                        </div>
                                                        <div>
                                                            <p class="text-[8px] font-bold text-[#706f6c] uppercase tracking-widest mb-1">Montant Encaissé</p>
                                                            <p class="font-black text-[#10b981]">{{ formatCurrency(m.extra?.montant) }} TND</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
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
    .mb-12 { margin-bottom: 2rem !important; }
    .divide-y > * { border-bottom-width: 1px !important; border-color: #eee !important; }
    nav { display: none !important; }
}
</style>

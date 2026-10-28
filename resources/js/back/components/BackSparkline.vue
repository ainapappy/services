<script setup lang="ts">
// Sparkline des dernieres latences d'un moniteur : polyline + aire a
// l'accent #6366f1, sans axes ni grille. Un point null (controle en
// echec) COUPE la courbe et est marque par un tick rouge sur la ligne de
// base : les coupures restent visibles. Cas limites : 0 point (rien,
// le parent affiche un repli), 1 point (tick a l'accent), valeurs
// toutes identiques (ligne aplat centree). Aucune animation : la
// sparkline est identique avec ou sans prefers-reduced-motion.
// Decorative (aria-hidden) : la description complete est portee par le
// <title> du svg (infobulle) et le texte environnant de la carte.
import { computed } from 'vue';
import type { SparklinePoint } from '@/types';

const props = defineProps<{
    points: SparklinePoint[];
}>();

/* ViewBox minimal : la sparkline est « minuscule », etiree en largeur
   par le CSS (preserveAspectRatio none + vector-effect pour garder des
   traits d'epaisseur constante). */
const WIDTH = 120;
const HEIGHT = 32;
const PAD = 3;

/* Ligne de base : la ou se posent les ticks de coupure et l'aire. */
const BASE_Y = HEIGHT - PAD;

const latencies = computed(() => props.points.map((point) => point.latencyMs));

const measurable = computed(() =>
    latencies.value.filter((value): value is number => value !== null),
);

const outageCount = computed(
    () => latencies.value.length - measurable.value.length,
);

/* Echelle verticale sur les seules valeurs mesurables : une latence
   elevee est dessinee vers le haut. */

function xAt(index: number): number {
    const last = props.points.length - 1;

    if (last <= 0) {
        return WIDTH / 2;
    }

    return PAD + (index / last) * (WIDTH - PAD * 2);
}

function yAt(value: number): number {
    const min = Math.min(...measurable.value);
    const max = Math.max(...measurable.value);

    if (max === min) {
        return HEIGHT / 2;
    }

    const ratio = (value - min) / (max - min);

    return HEIGHT - PAD - ratio * (HEIGHT - PAD * 2);
}

/* Segments continus : chaque suite de points mesurables consecutifs
   produit une polyline et son aire ; un null interrompt la courbe au
   lieu de la faire passer sous silence la coupure. Un segment reduit a
   un seul point ne se dessine pas en polyline : il est pose en tick. */
const segments = computed(() => {
    const runs: Array<{
        linePoints: string;
        areaPath: string;
        single: boolean;
        x: string;
    }> = [];
    let current: Array<{ index: number; value: number }> = [];

    function flush(): void {
        if (current.length === 0) {
            return;
        }

        const coords = current.map(
            ({ index, value }) => [xAt(index), yAt(value)] as const,
        );
        const linePoints = coords
            .map(([x, y]) => `${x.toFixed(2)},${y.toFixed(2)}`)
            .join(' ');
        const firstX = coords[0][0].toFixed(2);
        const lastX = coords[coords.length - 1][0].toFixed(2);
        const areaPath =
            `M ${firstX} ${BASE_Y.toFixed(2)} ` +
            coords
                .map(([x, y]) => `L ${x.toFixed(2)} ${y.toFixed(2)}`)
                .join(' ') +
            ` L ${lastX} ${BASE_Y.toFixed(2)} Z`;

        runs.push({
            linePoints,
            areaPath,
            single: coords.length === 1,
            x: firstX,
        });
        current = [];
    }

    latencies.value.forEach((value, index) => {
        if (value === null) {
            flush();
            return;
        }

        current.push({ index, value });
    });
    flush();

    return runs;
});

/* Segments dessinables (au moins deux points) : polyline + aire. */
const visibleSegments = computed(() =>
    segments.value
        .filter((segment) => !segment.single)
        .map((segment, index) => ({ ...segment, key: index })),
);

/* Points isoles (un seul controle mesurable) : tick vertical a l'accent. */
const singleSegments = computed(() =>
    segments.value
        .filter((segment) => segment.single)
        .map((segment, index) => ({ ...segment, key: index })),
);

/* Ticks de coupure : un par point null, poses sur la ligne de base. */
const outageMarks = computed(() =>
    latencies.value
        .map((value, index) => ({ value, index }))
        .filter((entry) => entry.value === null)
        .map((entry) => xAt(entry.index).toFixed(2)),
);

const title = computed(() => {
    const total = props.points.length;

    if (total === 0) {
        return 'Aucun contrôle récent';
    }

    if (outageCount.value === 0) {
        return `Latence des ${total} derniers contrôles, aucune coupure.`;
    }

    return `Latence des ${total} derniers contrôles, ${outageCount.value} coupure${outageCount.value > 1 ? 's' : ''} (ticks rouges).`;
});
</script>

<template>
    <svg
        v-if="points.length > 0"
        class="block h-8 w-full"
        :viewBox="`0 0 ${WIDTH} ${HEIGHT}`"
        preserveAspectRatio="none"
        aria-hidden="true"
        focusable="false"
    >
        <title>{{ title }}</title>

        <!-- Aire sous la courbe : un path par segment continu. -->
        <path
            v-for="segment in visibleSegments"
            :key="`area-${segment.key}`"
            class="fill-primary/15"
            :d="segment.areaPath"
        />

        <!-- Courbe : polyline par segment continu. -->
        <polyline
            v-for="segment in visibleSegments"
            :key="`line-${segment.key}`"
            class="fill-none stroke-primary stroke-2"
            :points="segment.linePoints"
            vector-effect="non-scaling-stroke"
        />

        <!-- Point isole (un seul controle mesurable dans le segment). -->
        <line
            v-for="segment in singleSegments"
            :key="`mark-${segment.key}`"
            class="stroke-primary stroke-2"
            stroke-linecap="round"
            :x1="segment.x"
            :y1="(HEIGHT / 2 - 3).toFixed(2)"
            :x2="segment.x"
            :y2="(HEIGHT / 2 + 3).toFixed(2)"
            vector-effect="non-scaling-stroke"
        />

        <!-- Coupures : tick rouge sur la ligne de base. -->
        <line
            v-for="(x, outageIndex) in outageMarks"
            :key="`outage-${outageIndex}`"
            class="stroke-destructive stroke-2"
            stroke-linecap="round"
            :x1="x"
            :y1="(BASE_Y - 4).toFixed(2)"
            :x2="x"
            :y2="BASE_Y.toFixed(2)"
            vector-effect="non-scaling-stroke"
        />
    </svg>
</template>

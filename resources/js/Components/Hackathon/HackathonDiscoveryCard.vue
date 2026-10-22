<script setup>
import { Link } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
    hackathon: {
        type: Object,
        required: true,
    },
});

defineEmits(["register"]);

const fallbackBanner = "https://images.unsplash.com/photo-1504384308090-c894fdcc538d?w=800&auto=format&fit=crop&q=60";

const statusInfo = computed(() => {
    const s = props.hackathon.status;
    if (props.hackathon.is_registration_open) {
        return { label: "Inscrições Abertas", badgeClass: "bg-success text-white" };
    }
    if (s === "registration") {
        return { label: "Inscrições Abertas", badgeClass: "bg-primary text-white" };
    }
    if (s === "submission") {
        return { label: "Em Desenvolvimento", badgeClass: "bg-warning text-dark" };
    }
    if (s === "voting") {
        return { label: "Em Avaliação", badgeClass: "bg-info text-dark" };
    }
    if (s === "finished") {
        return { label: "Encerrado", badgeClass: "bg-secondary text-white" };
    }
    if (props.hackathon.is_registration_closed) {
        return { label: "Inscrições Encerradas", badgeClass: "bg-secondary text-white" };
    }
    return { label: "Em Breve", badgeClass: "bg-info text-dark" };
});

const modalityLabel = computed(() => {
    const m = props.hackathon.modality;
    if (m === "online") return "Online";
    if (m === "presential") return "Presencial";
    if (m === "hybrid") return "Híbrido";
    return m ? m.charAt(0).toUpperCase() + m.slice(1) : "Online";
});

const participationLabel = computed(() => {
    const type = props.hackathon.registration_type;
    if (type === "individual") return "Individual";
    if (type === "team_only") return "Exclusivo por Equipe";
    return "Individual ou Equipe";
});

const registrationDeadlineFormatted = computed(() => {
    if (!props.hackathon.registration_end_date) return null;
    const date = new Date(props.hackathon.registration_end_date);
    return date.toLocaleDateString("pt-BR", { day: "2-digit", month: "short", year: "numeric" });
});
</script>

<template>
    <article class="hackathon-discovery-card">
        <div class="hackathon-discovery-card__media">
            <img
                :src="hackathon.banner_url || fallbackBanner"
                :alt="hackathon.title"
                loading="lazy"
            />

            <!-- Status Badge -->
            <span class="hackathon-discovery-card__status-badge badge" :class="statusInfo.badgeClass">
                {{ statusInfo.label }}
            </span>

            <!-- User Registration Status Badge -->
            <span v-if="hackathon.is_registered" class="hackathon-discovery-card__registered-pill">
                <font-awesome-icon :icon="['fas', 'circle-check']" class="me-1" />
                Inscrito {{ hackathon.user_registration?.status ? `(${hackathon.user_registration.status})` : '' }}
            </span>
        </div>

        <div class="hackathon-discovery-card__body">
            <!-- Metadata Tags -->
            <div class="hackathon-discovery-card__meta">
                <span class="badge bg-light text-dark border badge-modality">
                    <font-awesome-icon :icon="['fas', 'location-dot']" class="me-1" />
                    {{ modalityLabel }}
                </span>
                <span v-if="hackathon.category" class="badge bg-primary-subtle text-primary border border-primary-subtle">
                    {{ hackathon.category }}
                </span>
                <span class="badge bg-secondary-subtle text-secondary">
                    {{ participationLabel }}
                </span>
            </div>

            <!-- Title -->
            <h3 class="hackathon-discovery-card__title">
                <Link href="#">
                    {{ hackathon.title }}
                </Link>
            </h3>

            <!-- Organization -->
            <div class="hackathon-discovery-card__organization">
                <font-awesome-icon :icon="['fas', 'building']" class="text-muted" />
                <span class="text-truncate">
                    {{ hackathon.organization?.name || 'Motirõ Hub' }}
                </span>
            </div>

            <!-- Description -->
            <p class="hackathon-discovery-card__description">
                {{ hackathon.description || 'Desafio colaborativo sem descrição detalhada cadastrada.' }}
            </p>

            <!-- Registration Deadline note -->
            <div v-if="registrationDeadlineFormatted" class="small text-muted mb-3 d-flex align-items-center gap-1">
                <font-awesome-icon :icon="['fas', 'calendar-days']" class="text-primary" />
                <span>Inscrições até <strong>{{ registrationDeadlineFormatted }}</strong></span>
            </div>

            <!-- Card Footer with Stats & Actions -->
            <div class="hackathon-discovery-card__footer">
                <div class="hackathon-discovery-card__actions">
                    <Link
                        href="#"
                        class="btn btn-sm btn-outline-secondary"
                        title="Ver página de detalhes e regulamento"
                    >
                        Detalhes
                    </Link>

                    <button
                        v-if="!hackathon.is_registered && hackathon.is_registration_open"
                        type="button"
                        class="btn btn-sm btn-primary"
                        @click="$emit('register', hackathon)"
                    >
                        Inscrever-se
                    </button>

                    <span
                        v-else-if="hackathon.is_registered"
                        class="btn btn-sm btn-outline-success disabled"
                    >
                        Inscrito
                    </span>

                    <span
                        v-else-if="hackathon.is_registration_closed"
                        class="btn btn-sm btn-light border text-muted disabled"
                    >
                        Encerrado
                    </span>
                </div>
            </div>
        </div>
    </article>
</template>

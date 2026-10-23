<script setup>
import { useForm, Head, Link, router } from "@inertiajs/vue3";
import { ref } from "vue";
import RegisterInvitationCard from "./Partials/RegisterInvitationCard.vue";
import RegisterStepper from "./Partials/RegisterStepper.vue";
import RegisterUserBanner from "./Partials/RegisterUserBanner.vue";
import RegisterStepAccount from "./Partials/RegisterStepAccount.vue";
import RegisterStepOrg from "./Partials/RegisterStepOrg.vue";
import RegisterStepReview from "./Partials/RegisterStepReview.vue";

const props = defineProps({
    authUser: {
        type: Object,
        default: null,
    },
    categories: {
        type: Array,
        default: () => [
            "Empresa",
            "Universidade ou instituição de ensino",
            "Comunidade ou coletivo",
            "Organização social / Sem fins lucrativos",
            "Instituição pública",
            "Startup",
            "Outra",
        ],
    },
    invitation: {
        type: Object,
        default: null,
    },
});

const currentUser = ref(props.authUser || null);
const currentStep = ref(props.authUser ? 2 : 1);
const logoPreview = ref(null);

const orgForm = useForm({
    organization_name: "",
    organization_type: props.categories[0] || "Empresa",
    organization_email: "",
    website: "",
    description: "",
    logo: null,
});

const handleAuthenticated = (user) => {
    currentUser.value = user;
    currentStep.value = 2;
};

const logoutCurrentIdentity = () => {
    router.post(route("logout"), {}, {
        onSuccess: () => {
            currentUser.value = null;
            currentStep.value = 1;
        },
    });
};

const submitOrganization = () => {
    orgForm.post(route("organization.register.store"), {
        preserveScroll: true,
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="Cadastrar Organização | Motirõ" />

    <div class="py-2">
        <!-- Brand Header -->
        <div class="text-center mb-4">
            <Link :href="route('home')" class="text-decoration-none">
                <h1 class="fw-bold text-white fs-3 mb-1 tracking-tight">Motirõ</h1>
            </Link>
            <p class="text-light small mb-0">
                Plataforma de gestão integrada de hackathons e ecossistemas de inovação.
            </p>
        </div>

        <RegisterInvitationCard
            v-if="invitation"
            :invitation="invitation"
        />

        <div v-else>
            <RegisterStepper :current-step="currentStep" />

            <RegisterUserBanner
                v-if="currentUser && currentStep >= 2"
                :user="currentUser"
                @logout="logoutCurrentIdentity"
            />

            <RegisterStepAccount
                v-if="currentStep === 1"
                @authenticated="handleAuthenticated"
            />

            <RegisterStepOrg
                v-else-if="currentStep === 2"
                :org-form="orgForm"
                :categories="categories"
                :can-go-back="!props.authUser"
                v-model:logo-preview="logoPreview"
                @back="currentStep = 1"
                @next="currentStep = 3"
            />

            <RegisterStepReview
                v-else-if="currentStep === 3"
                :current-user="currentUser"
                :org-form="orgForm"
                :logo-preview="logoPreview"
                @back="currentStep = 2"
                @edit-org="currentStep = 2"
                @submit="submitOrganization"
            />
        </div>

        <div class="text-center mt-4 pt-3 border-top border-secondary-subtle">
            <Link :href="route('home')" class="text-white text-decoration-none small">
                <font-awesome-icon :icon="['fas', 'arrow-left']" class="me-1" />
                Voltar para a página inicial
            </Link>
        </div>
    </div>
</template>

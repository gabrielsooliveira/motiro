<script setup>
import { Link } from "@inertiajs/vue3";
import HackathonHeaderBanner from "@/js/Components/HackathonHeaderBanner.vue";

const hackathon = {
    banner: "https://dummyimage.com/1200x400/ccc/ffffff&text=+",
    logo: "https://dummyimage.com/200x200/abc555/ffffff&text=Logo",
    title: "Motirõ For Hackers",
    subtitle:
        "Crie soluções que transformam vidas através da tecnologia colaborativa.",
    tags: ["Impacto Social", "Web3", "Comunidade"],
};
</script>

<template>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center my-4">
            <div class="mt-3">
                <Link
                    class="text-decoration-none fw-semibold"
                    :href="route('hackathons.index')"
                >
                    <font-awesome-icon
                        icon="fa-solid fa-circle-left"
                    ></font-awesome-icon>
                    Voltar
                </Link>
            </div>

            <div class="mt-3">
                <Link class="btn btn-blue btn-sm fw-semibold shadow-sm">
                    <font-awesome-icon
                        icon="fa-solid fa-user-plus"
                        class="me-2"
                    ></font-awesome-icon>
                    Inscreva-se
                </Link>
            </div>
        </div>

        <HackathonHeaderBanner :hackathon="hackathon" />

        <div class="d-flex align-items-start">
            <div
                class="nav flex-column nav-pills me-3"
                id="v-pills-tab"
                role="tablist"
                aria-orientation="vertical"
            >
                <button
                    class="nav-link active"
                    id="v-pills-home-tab"
                    data-bs-toggle="pill"
                    data-bs-target="#v-pills-home"
                    type="button"
                    role="tab"
                    aria-controls="v-pills-home"
                    aria-selected="true"
                >
                    Home
                </button>
                <button
                    class="nav-link"
                    id="v-pills-profile-tab"
                    data-bs-toggle="pill"
                    data-bs-target="#v-pills-profile"
                    type="button"
                    role="tab"
                    aria-controls="v-pills-profile"
                    aria-selected="false"
                >
                    Profile
                </button>
                <button
                    class="nav-link"
                    id="v-pills-disabled-tab"
                    data-bs-toggle="pill"
                    data-bs-target="#v-pills-disabled"
                    type="button"
                    role="tab"
                    aria-controls="v-pills-disabled"
                    aria-selected="false"
                    disabled
                >
                    Disabled
                </button>
                <button
                    class="nav-link"
                    id="v-pills-messages-tab"
                    data-bs-toggle="pill"
                    data-bs-target="#v-pills-messages"
                    type="button"
                    role="tab"
                    aria-controls="v-pills-messages"
                    aria-selected="false"
                >
                    Messages
                </button>
                <button
                    class="nav-link"
                    id="v-pills-settings-tab"
                    data-bs-toggle="pill"
                    data-bs-target="#v-pills-settings"
                    type="button"
                    role="tab"
                    aria-controls="v-pills-settings"
                    aria-selected="false"
                >
                    Settings
                </button>
            </div>
            <div class="tab-content" id="v-pills-tabContent">
                <div
                    class="tab-pane fade show active"
                    id="v-pills-home"
                    role="tabpanel"
                    aria-labelledby="v-pills-home-tab"
                    tabindex="0"
                >
                    ...
                </div>
                <div
                    class="tab-pane fade"
                    id="v-pills-profile"
                    role="tabpanel"
                    aria-labelledby="v-pills-profile-tab"
                    tabindex="0"
                >
                    ...
                </div>
                <div
                    class="tab-pane fade"
                    id="v-pills-disabled"
                    role="tabpanel"
                    aria-labelledby="v-pills-disabled-tab"
                    tabindex="0"
                >
                    ...
                </div>
                <div
                    class="tab-pane fade"
                    id="v-pills-messages"
                    role="tabpanel"
                    aria-labelledby="v-pills-messages-tab"
                    tabindex="0"
                >
                    ...
                </div>
                <div
                    class="tab-pane fade"
                    id="v-pills-settings"
                    role="tabpanel"
                    aria-labelledby="v-pills-settings-tab"
                    tabindex="0"
                >
                    ...
                </div>
            </div>
        </div>
    </div>
</template>

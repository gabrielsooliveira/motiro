<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { ref, onMounted, onBeforeUnmount, computed, watch } from "vue";

const page = usePage();
const user = computed(() => page.props.user || page.props.auth?.user);
const context = computed(() => page.props.context || {});
const activeOrg = computed(() => context.value.active_organization);
const userOrgs = computed(() => context.value.user_organizations || []);

const dashboardDestination = computed(() => {
    if (context.value.type === "organization") {
        if (activeOrg.value) {
            return {
                url: route("organization.dashboard"),
                label: `Painel: ${activeOrg.value.name}`,
                icon: "building",
            };
        }
        if (userOrgs.value.length === 1) {
            return {
                url: route("organization.dashboard"),
                label: "Painel da Organização",
                icon: "building",
            };
        }
        return {
            url: route("organization.select"),
            label: "Selecionar Organização",
            icon: "building",
        };
    }

    return {
        url: route("participant.dashboard"),
        label: "Meu Painel",
        icon: "user-astronaut",
    };
});

const isMenuOpen = ref(false);
const isScrolled = ref(false);
const isMdUp = ref(typeof window !== "undefined" ? window.innerWidth >= 992 : true);

// Dynamic reactive path from Inertia (stripping query strings and trailing slashes)
const currentPath = computed(() => {
    const rawUrl = page.url || "";
    const cleanUrl = rawUrl.split("?")[0].split("#")[0];
    return cleanUrl.length > 1 ? cleanUrl.replace(/\/+$/, "") : cleanUrl;
});

const isHomeActive = computed(() => {
    return currentPath.value === "" || currentPath.value === "/";
});

const isHackathonsActive = computed(() => {
    return currentPath.value.startsWith("/hackathons");
});

const isGuildsActive = computed(() => {
    return currentPath.value.startsWith("/guilds");
});

const isNewsActive = computed(() => {
    return currentPath.value.startsWith("/noticias");
});

const handleScroll = () => {
    if (typeof window !== "undefined") {
        isScrolled.value = window.scrollY > 8;
    }
};

const handleResize = () => {
    if (typeof window !== "undefined") {
        isMdUp.value = window.innerWidth >= 992;
        if (isMdUp.value && isMenuOpen.value) {
            closeMenu();
        }
    }
};

const toggleMenu = () => {
    isMenuOpen.value = !isMenuOpen.value;
};

const closeMenu = () => {
    isMenuOpen.value = false;
};

const handleMobileNavClick = () => {
    setTimeout(() => {
        closeMenu();
    }, 50);
};

watch(
    () => page.url,
    () => {
        closeMenu();
        handleScroll();
    }
);

const handleClickOutside = (event) => {
    const modalMenu = document.querySelector(".mobile-menu");
    const toggler = document.querySelector(".navbar-toggler");

    if (
        isMenuOpen.value &&
        modalMenu &&
        !modalMenu.contains(event.target) &&
        toggler &&
        !toggler.contains(event.target)
    ) {
        closeMenu();
    }
};

watch(isMenuOpen, (isOpen) => {
    if (typeof document !== "undefined") {
        document.body.style.overflow = isOpen ? "hidden" : "";
    }
});

onMounted(() => {
    handleScroll();
    handleResize();
    document.addEventListener("click", handleClickOutside);
    window.addEventListener("scroll", handleScroll, { passive: true });
    window.addEventListener("resize", handleResize);
});

onBeforeUnmount(() => {
    document.removeEventListener("click", handleClickOutside);
    window.removeEventListener("scroll", handleScroll);
    window.removeEventListener("resize", handleResize);
    if (typeof document !== "undefined") {
        document.body.style.overflow = "";
    }
});

const navbarClass = computed(() => {
    return [
        "navbar navbar-expand-lg navbar-site fixed-top z-3",
        { "navbar-site-scrolled": !isMdUp.value || isScrolled.value },
    ];
});
</script>

<template>
    <nav :class="navbarClass">
        <div class="container d-flex align-items-center justify-content-between">
            <Link
                class="navbar-brand fw-bold fs-4 title-page brand-logo text-decoration-none py-0"
                :href="route('home')"
            >
                Motirõ
            </Link>

            <div class="collapse navbar-collapse d-none d-lg-flex align-items-center justify-content-between ms-lg-4">
                <ul class="site-nav-clean mx-auto">
                    <li>
                        <Link
                            :href="route('home')"
                            class="site-nav-clean-link"
                            :class="{ active: isHomeActive }"
                            :aria-current="isHomeActive ? 'page' : undefined"
                        >
                            Início
                        </Link>
                    </li>
                    <li>
                        <Link
                            :href="route('hackathon.index')"
                            class="site-nav-clean-link"
                            :class="{ active: isHackathonsActive }"
                            :aria-current="isHackathonsActive ? 'page' : undefined"
                        >
                            Hackathons
                        </Link>
                    </li>
                    <li>
                        <Link
                            :href="route('guild.index')"
                            class="site-nav-clean-link"
                            :class="{ active: isGuildsActive }"
                            :aria-current="isGuildsActive ? 'page' : undefined"
                            disabled
                        >
                            Guilds
                        </Link>
                    </li>
                    <li>
                        <Link
                            :href="route('news.index')"
                            class="site-nav-clean-link"
                            :class="{ active: isNewsActive }"
                            :aria-current="isNewsActive ? 'page' : undefined"
                            disabled
                        >
                            Notícias
                        </Link>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-2">
                    <template v-if="user">
                        <Link
                            :href="route('participant.dashboard')"
                            class="btn btn-primary fw-semibold btn-sm px-3 py-2 d-flex align-items-center gap-2 rounded-3 shadow-sm"
                        >
                            <span>{{ dashboardDestination.label }}</span>
                            <font-awesome-icon :icon="['fas', dashboardDestination.icon]" />
                        </Link>
                        <Link
                            v-if="userOrgs.length > 0"
                            :href="route('organization.select')"
                            class="btn btn-outline-secondary fw-semibold btn-sm px-2 py-2 rounded-3"
                            title="Alternar Contexto"
                        >
                            <font-awesome-icon :icon="['fas', 'rotate']" />
                        </Link>
                    </template>

                    <template v-else>
                        <Link
                            :href="route('select.login')"
                            class="btn btn-link link-secondary text-decoration-none fw-semibold px-3 py-2"
                        >
                            Entrar
                        </Link>
                        <Link
                            :href="route('participant.register')"
                            class="btn btn-outline-primary fw-semibold btn-sm px-3 py-2 rounded-3"
                        >
                            Quero Participar
                        </Link>
                        <Link
                            :href="route('organization.register')"
                            class="btn btn-primary fw-semibold btn-sm px-3 py-2 rounded-3 shadow-sm"
                        >
                            Organizar Hackathon
                        </Link>
                    </template>
                </div>
            </div>

            <button
                class="btn d-lg-none border-0 navbar-toggler p-2 text-dark"
                type="button"
                @click="toggleMenu"
                aria-label="Abrir Menu"
            >
                <font-awesome-icon :icon="['fas', 'bars']" size="lg" />
            </button>
        </div>
    </nav>

    <transition name="mobile-drawer">
        <div v-if="isMenuOpen" class="mobile-menu site-mobile-drawer position-fixed top-0 start-0 w-100 h-100 d-flex flex-column p-4 z-3 overflow-y-auto">
            <div class="d-flex justify-content-between align-items-center pb-3 mb-4 border-bottom border-light-subtle">
                <Link
                    class="navbar-brand fw-bold fs-4 title-page brand-logo text-decoration-none"
                    :href="route('home')"
                    @click="handleMobileNavClick"
                >
                    Motirõ
                </Link>
                <button
                    class="btn btn-light rounded-circle p-2 d-flex align-items-center justify-content-center text-secondary site-mobile-close-btn"
                    @click="closeMenu"
                    aria-label="Fechar Menu"
                >
                    <font-awesome-icon :icon="['fas', 'xmark']" class="fs-5" />
                </button>
            </div>

            <nav class="site-mobile-nav-list mb-4">
                <Link
                    :href="route('home')"
                    class="site-mobile-nav-link"
                    :class="{ active: isHomeActive }"
                    :aria-current="isHomeActive ? 'page' : undefined"
                    @click="handleMobileNavClick"
                >
                    <span>Início</span>
                    <span v-if="isHomeActive" class="site-mobile-nav-dot" aria-hidden="true"></span>
                </Link>

                <Link
                    :href="route('hackathon.index')"
                    class="site-mobile-nav-link"
                    :class="{ active: isHackathonsActive }"
                    :aria-current="isHackathonsActive ? 'page' : undefined"
                    @click="handleMobileNavClick"
                >
                    <span>Hackathons</span>
                    <span v-if="isHackathonsActive" class="site-mobile-nav-dot" aria-hidden="true"></span>
                </Link>

                <Link
                    :href="route('guild.index')"
                    class="site-mobile-nav-link"
                    :class="{ active: isGuildsActive }"
                    :aria-current="isGuildsActive ? 'page' : undefined"
                    @click="handleMobileNavClick"
                >
                    <span>Guilds</span>
                    <span v-if="isGuildsActive" class="site-mobile-nav-dot" aria-hidden="true"></span>
                </Link>

                <Link
                    :href="route('news.index')"
                    class="site-mobile-nav-link"
                    :class="{ active: isNewsActive }"
                    :aria-current="isNewsActive ? 'page' : undefined"
                    @click="handleMobileNavClick"
                >
                    <span>Noticias</span>
                    <span v-if="isNewsActive" class="site-mobile-nav-dot" aria-hidden="true"></span>
                </Link>
            </nav>

            <div class="mt-auto pt-3 border-top border-light-subtle d-grid gap-2">
                <template v-if="user">
                    <div class="text-muted small mb-1 px-1">
                        Conectado como <strong class="text-dark">{{ user.name }}</strong>
                    </div>
                    <Link
                        :href="dashboardDestination.url"
                        class="btn btn-primary btn-lg fw-semibold d-flex align-items-center justify-content-center gap-2 rounded-3 shadow-sm"
                        @click="handleMobileNavClick"
                    >
                        <span>{{ dashboardDestination.label }}</span>
                        <font-awesome-icon :icon="['fas', dashboardDestination.icon]" />
                    </Link>
                    <Link
                        v-if="userOrgs.length > 0"
                        :href="route('context.select')"
                        class="btn btn-outline-secondary btn-lg fw-semibold d-flex align-items-center justify-content-center gap-2 rounded-3"
                        @click="handleMobileNavClick"
                    >
                        <span>Alternar Contexto</span>
                        <font-awesome-icon :icon="['fas', 'rotate']" />
                    </Link>
                </template>

                <template v-else>
                    <Link
                        :href="route('select.login')"
                        class="btn btn-outline-secondary btn-lg fw-semibold rounded-3"
                        @click="handleMobileNavClick"
                    >
                        Entrar na Conta
                    </Link>
                    <Link
                        :href="route('participant.register')"
                        class="btn btn-outline-primary btn-lg fw-semibold rounded-3"
                        @click="handleMobileNavClick"
                    >
                        Quero Participar
                    </Link>
                    <Link
                        :href="route('organization.register')"
                        class="btn btn-primary btn-lg fw-semibold rounded-3 shadow-sm"
                        @click="handleMobileNavClick"
                    >
                        Organizar Hackathon
                    </Link>
                </template>
            </div>
        </div>
    </transition>
</template>

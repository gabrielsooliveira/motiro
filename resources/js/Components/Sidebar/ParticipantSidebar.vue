<script setup>
import { Link } from "@inertiajs/vue3";
import SidebarBase from "@/js/Components/Sidebar/SidebarBase.vue";

</script>

<template>
    <SidebarBase badge-label="Participante" badge-class="brand-badge">
        <template #default="{ closeMenu }">
            <div class="d-flex flex-column gap-3">
                <div>
                    <div class="nav-section-title px-2 mb-1 text-uppercase">
                        Minha Área
                    </div>
                    <ul class="nav flex-column gap-1 list-unstyled mb-0">
                        <li class="nav-item">
                            <Link
                                class="nav-link-custom"
                                :href="route('participant.dashboard')"
                                :class="{ active: $page.url === '/participant' || $page.url === '/participant/dashboard' }"
                                @click="closeMenu"
                            >
                                <font-awesome-icon :icon="['fas', 'house']" class="nav-icon" />
                                <span>Meu Painel</span>
                            </Link>
                        </li>
                        <li class="nav-item">
                            <Link
                                class="nav-link-custom"
                                :href="route('participant.guilds.index')"
                                :class="{ active: $page.url.startsWith('/participant/guilds') }"
                                @click="closeMenu"
                            >
                                <font-awesome-icon :icon="['fas', 'shield-halved']" class="nav-icon" />
                                <span>Guilds</span>
                            </Link>
                        </li>
                    </ul>
                </div>

                <div>
                    <div class="nav-section-title px-2 mb-1 text-uppercase">
                        Explorar
                    </div>
                    <ul class="nav flex-column gap-1 list-unstyled mb-0">
                        <li class="nav-item">
                            <Link
                                class="nav-link-custom"
                                :href="route('participant.hackathon.index')"
                                :class="{ active: $page.url.startsWith('/participant/hackathons') }"
                                @click="closeMenu"
                            >
                                <font-awesome-icon :icon="['fas', 'magnifying-glass']" class="nav-icon" />
                                <span>Explorar Hackathons</span>
                            </Link>
                        </li>
                    </ul>
                </div>
            </div>
        </template>
    </SidebarBase>
</template>

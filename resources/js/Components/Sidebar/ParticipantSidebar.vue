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
                                :class="{ active: $page.url === '/participante' || $page.url === '/participante/dashboard' || $page.url === '/dashboard' }"
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
                                :class="{ active: $page.url.startsWith('/participante/guildas') || $page.url.startsWith('/dashboard/guilds') }"
                                @click="closeMenu"
                            >
                                <font-awesome-icon :icon="['fas', 'shield-halved']" class="nav-icon" />
                                <span>Minhas Guildas</span>
                            </Link>
                        </li>
                        <li class="nav-item">
                            <Link
                                class="nav-link-custom"
                                :href="route('participant.teams.index')"
                                :class="{ active: $page.url.startsWith('/participante/equipes') || $page.url.startsWith('/participante/grupos') || $page.url.startsWith('/dashboard/teams') || $page.url.startsWith('/dashboard/groups') }"
                                @click="closeMenu"
                            >
                                <font-awesome-icon :icon="['fas', 'people-group']" class="nav-icon" />
                                <span>Equipes de Hackathon</span>
                            </Link>
                        </li>
                        <li class="nav-item">
                            <Link
                                class="nav-link-custom"
                                :href="route('participant.my_hackathons')"
                                :class="{ active: $page.url.startsWith('/participante/meus-hackathons') || $page.url.startsWith('/dashboard/my-hackathons') }"
                                @click="closeMenu"
                            >
                                <font-awesome-icon :icon="['fas', 'trophy']" class="nav-icon" />
                                <span>Meus Hackathons</span>
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
                                :href="route('participant.hackathons')"
                                :class="{ active: $page.url.startsWith('/participante/hackathons') || $page.url.startsWith('/dashboard/hackathons') }"
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

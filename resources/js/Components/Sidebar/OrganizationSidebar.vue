<script setup>
import { computed } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import SidebarBase from "@/js/Components/Sidebar/SidebarBase.vue";

const page = usePage();
const user = computed(() => page.props.user || page.props.auth?.user);
const context = computed(() => page.props.context || {});
const activeOrg = computed(() => context.value.active_organization);
const hasParticipantProfile = computed(() => !!user.value?.has_participant_profile);
</script>

<template>
    <SidebarBase :badge-label="activeOrg ? activeOrg.name : 'Organização'" badge-class="brand-badge text-truncate">
        <template #default="{ closeMenu }">
            <div class="d-flex flex-column gap-3">
                <div>
                    <div class="nav-section-title px-2 mb-1 text-uppercase">
                        Geral
                    </div>
                    <ul class="nav flex-column gap-1 list-unstyled mb-0">
                        <li class="nav-item">
                            <Link
                                class="nav-link-custom"
                                :href="route('organization.dashboard')"
                                :class="{ active: $page.url === '/organization/dashboard' || $page.url === '/organizacao/dashboard' }"
                                @click="closeMenu"
                            >
                                <font-awesome-icon :icon="['fas', 'chart-line']" class="nav-icon" />
                                <span>Dashboard</span>
                            </Link>
                        </li>
                        <li class="nav-item">
                            <Link
                                class="nav-link-custom"
                                :href="route('hackathon.manage.create')"
                                :class="{ active: $page.url.includes('/hackathons/create') }"
                                @click="closeMenu"
                            >
                                <font-awesome-icon :icon="['fas', 'circle-plus']" class="nav-icon" />
                                <span>Criar Hackathon</span>
                            </Link>
                        </li>
                    </ul>
                </div>

                <div>
                    <div class="nav-section-title px-2 mb-1 text-uppercase">
                        Gestão Institucional
                    </div>
                    <ul class="nav flex-column gap-1 list-unstyled mb-0">
                        <li class="nav-item">
                            <Link
                                class="nav-link-custom"
                                :href="route('organization.users.index')"
                                :class="{ active: $page.url.startsWith('/organization/users') || $page.url.startsWith('/organizacao/users') }"
                                @click="closeMenu"
                            >
                                <font-awesome-icon :icon="['fas', 'users-gear']" class="nav-icon" />
                                <span>Membros e Convites</span>
                            </Link>
                        </li>
                        <li class="nav-item">
                            <Link
                                class="nav-link-custom"
                                :href="route('organization.select')"
                                :class="{ active: $page.url.includes('selecionar') }"
                                @click="closeMenu"
                            >
                                <font-awesome-icon :icon="['fas', 'arrow-right-arrow-left']" class="nav-icon" />
                                <span>Trocar Organização</span>
                            </Link>
                        </li>
                    </ul>
                </div>

                <div>
                    <div class="nav-section-title px-2 mb-1 text-uppercase">
                        Área do Participante
                    </div>
                    <ul class="nav flex-column gap-1 list-unstyled mb-0">
                        <li v-if="hasParticipantProfile" class="nav-item">
                            <Link
                                class="nav-link-custom"
                                :href="route('participant.dashboard')"
                                @click="closeMenu"
                            >
                                <font-awesome-icon :icon="['fas', 'user-astronaut']" class="nav-icon" />
                                <span>Meu Painel Participante</span>
                            </Link>
                        </li>
                        <li v-else class="nav-item">
                            <Link
                                class="nav-link-custom"
                                :href="route('participant.complete_profile')"
                                @click="closeMenu"
                            >
                                <font-awesome-icon :icon="['fas', 'user-plus']" class="nav-icon" />
                                <span>Ativar Perfil Participante</span>
                            </Link>
                        </li>
                        <li class="nav-item">
                            <Link
                                class="nav-link-custom"
                                :href="route('context.select')"
                                @click="closeMenu"
                            >
                                <font-awesome-icon :icon="['fas', 'layer-group']" class="nav-icon" />
                                <span>Selecionar Contexto</span>
                            </Link>
                        </li>
                    </ul>
                </div>
            </div>
        </template>
    </SidebarBase>
</template>

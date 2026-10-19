<script setup>
import { Link } from "@inertiajs/vue3";
import SidebarBase from "@/js/Components/Sidebar/SidebarBase.vue";
</script>

<template>
    <SidebarBase badge-label="Organização" badge-class="brand-badge">
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
                                <span>Usuários da Instituição</span>
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
            </div>
        </template>
    </SidebarBase>
</template>

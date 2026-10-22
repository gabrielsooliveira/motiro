<script setup>
import { Link } from "@inertiajs/vue3";

defineProps({
    organizations: {
        type: Array,
        default: () => [],
    },
    hackathons: {
        type: Array,
        default: () => [],
    },
});

defineEmits(["create-hackathon"]);
</script>

<template>
    <div class="organizer-section">
        <div class="row g-4">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3">
                        <h5 class="card-title mb-0 fw-bold">
                            <font-awesome-icon :icon="['fas', 'building']" class="text-primary me-2" />
                            Gerenciamento de Hackathons & Organizações
                        </h5>
                        <button v-if="organizations.length > 0" class="btn btn-sm btn-primary" @click="$emit('create-hackathon')">
                            + Novo Hackathon
                        </button>
                    </div>
                    <div class="card-body">
                        <div v-if="hackathons.length === 0" class="text-center py-4 text-muted">
                            <p class="mb-2">Nenhum Hackathon criado pela sua organização.</p>
                            <button v-if="organizations.length > 0" class="btn btn-sm btn-primary" @click="$emit('create-hackathon')">
                                Criar Primeiro Hackathon
                            </button>
                            <Link v-else href="#" class="btn btn-sm btn-primary">
                                Cadastrar Organização
                            </Link>
                        </div>
                        <div v-else class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Hackathon</th>
                                        <th>Modalidade</th>
                                        <th>Status</th>
                                        <th>Inscritos</th>
                                        <th>Projetos</th>
                                        <th class="text-end">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="hack in hackathons" :key="hack.id">
                                        <td>
                                            <h6 class="mb-0 fw-bold">{{ hack.title }}</h6>
                                            <small class="text-muted">Slug: /{{ hack.slug }}</small>
                                        </td>
                                        <td><span class="text-capitalize">{{ hack.modality }}</span></td>
                                        <td><span class="badge bg-secondary text-capitalize">{{ hack.status }}</span></td>
                                        <td>{{ hack.registrations_count ?? 0 }}</td>
                                        <td>{{ hack.projects_count ?? 0 }}</td>
                                        <td class="text-end">
                                            <Link href="#" class="btn btn-sm btn-primary">
                                                Gerenciar
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

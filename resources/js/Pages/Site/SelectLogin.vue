<script setup>
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head>
        <title>Acesso à Plataforma</title>
    </Head>

    <div class="d-flex flex-column justify-content-center align-items-center pt-5">
        <div class="container select-login-container">
            <div class="text-center mb-5">
                <Link :href="route('home')" class="text-decoration-none">
                    <span class="fs-2 fw-bold text-dark">Motirõ</span>
                </Link>
                <h1 class="h3 fw-bold text-dark mt-2 mb-1">Como você deseja acessar a plataforma?</h1>
                <p class="text-muted">Selecione a experiência correspondente ao seu objetivo.</p>
            </div>

            <div class="row g-4 justify-content-center">
                <!-- Competitor / Participant Portal -->
                <div class="col-12 col-md-6">
                    <div class="card h-100 border-0 shadow-sm rounded-4 portal-card p-4 d-flex flex-column justify-content-between">
                        <div>
                            <div class="icon-bubble bg-warning bg-opacity-15 text-warning mb-3 d-flex align-items-center justify-content-center rounded-3">
                                <font-awesome-icon :icon="['fas', 'user-astronaut']" class="fs-3 text-warning" />
                            </div>
                            <span class="badge bg-warning-subtle text-warning-emphasis mb-2">Área do Participante</span>
                            <h2 class="h5 fw-bold text-dark mb-2">Participar de Hackathons</h2>
                            <p class="text-muted small mb-0">
                                Para pessoas que desejam descobrir eventos abertos, formar equipes multidisciplinares, resolver desafios práticos e submeter projetos para avaliação.
                            </p>
                        </div>

                        <div class="mt-4 pt-3 border-top">
                            <Link
                                :href="route('participant.login')"
                                class="btn btn-warning w-100 fw-bold d-flex align-items-center justify-content-center gap-2 py-2 mb-2 shadow-sm text-dark"
                            >
                                <span>Acessar como Participante</span>
                                <font-awesome-icon :icon="['fas', 'arrow-right']" class="small" />
                            </Link>

                            <div class="text-center">
                                <Link
                                    :href="route('participant.register')"
                                    class="text-decoration-none small text-muted"
                                >
                                    Não tem conta? <span class="text-dark fw-semibold">Cadastre-se</span>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Organization Portal -->
                <div class="col-12 col-md-6">
                    <div class="card h-100 border-0 shadow-sm rounded-4 portal-card p-4 d-flex flex-column justify-content-between">
                        <div>
                            <div class="icon-bubble bg-primary bg-opacity-10 text-primary mb-3 d-flex align-items-center justify-content-center rounded-3">
                                <font-awesome-icon :icon="['fas', 'building']" class="fs-3 text-primary" />
                            </div>
                            <span class="badge bg-primary-subtle text-primary mb-2">Área da Organização</span>
                            <h2 class="h5 fw-bold text-dark mb-2">Gerenciar Hackathons</h2>
                            <p class="text-muted small mb-0">
                                Para empresas, universidades, órgãos públicos e comunidades que desejam criar eventos, gerenciar equipes de colaboradores, acompanhar inscrições e avaliar soluções.
                            </p>
                        </div>

                        <div class="mt-4 pt-3 border-top">
                            <Link
                                :href="route('organization.login')"
                                class="btn btn-primary w-100 fw-bold d-flex align-items-center justify-content-center gap-2 py-2 mb-2 shadow-sm text-white"
                            >
                                <span>Acessar como Organização</span>
                                <font-awesome-icon :icon="['fas', 'arrow-right']" class="small" />
                            </Link>

                            <div class="text-center">
                                <Link
                                    :href="route('organization.register')"
                                    class="text-decoration-none small text-muted"
                                >
                                    Nova instituição? <span class="text-primary fw-semibold">Cadastrar organização</span>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
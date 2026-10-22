<script setup>
import { Head, Link } from "@inertiajs/vue3";
import AnimateText from "@/js/Components/AnimateText.vue";
import HeroImages from "@/js/Components/Hero/HeroImages.vue";

const animatedWords = ["pessoas", "ideias", "projetos"];

const pillars = [
    {
        icon: ["fas", "user-astronaut"],
        iconColor: "text-primary",
        title: "Competidores",
        description: "Estudantes e profissionais que buscam resolver problemas reais e aprender na prática.",
    },
    {
        icon: ["fas", "users"],
        iconColor: "text-success",
        title: "Equipes Multidisciplinares",
        description: "Formação colaborativa de times com diferentes habilidades para potencializar a execução dos projetos.",
    },
    {
        icon: ["fas", "building"],
        iconColor: "text-info",
        title: "Organizações",
        description: "Empresas, universidades, órgãos públicos e startups que lançam desafios e descobrem grandes talentos.",
    },
    {
        icon: ["fas", "gavel"],
        iconColor: "text-warning",
        title: "Juízes Especialistas",
        description: "Bancas avaliadoras com critérios ponderados, feedback construtivo e imparcialidade garantida.",
    },
    {
        icon: ["fas", "lightbulb"],
        iconColor: "text-danger",
        title: "Projetos de Impacto",
        description: "Soluções tecnológicas concretas entregues com pitch, demonstração e repositórios auditáveis.",
    },
];

const steps = [
    {
        number: "01",
        title: "Encontre um Hackathon",
        description: "Navegue pela lista de eventos disponíveis, filtre por categoria ou modalidade (online, presencial ou híbrido) e consulte o regulamento.",
    },
    {
        number: "02",
        title: "Monte sua Equipe",
        description: "Crie seu time ou aceite convites de outros participantes, definindo papéis e alinhando tecnologias.",
    },
    {
        number: "03",
        title: "Inscreva-se no Evento",
        description: "Inscreva sua equipe com poucos cliques e acompanhe o status de aprovação em tempo real.",
    },
    {
        number: "04",
        title: "Desenvolva sua Solução",
        description: "Siga o cronograma de etapas personalizadas, colabore com seu time e acompanhe os prazos limite.",
    },
    {
        number: "05",
        title: "Apresente seu Projeto",
        description: "Envie sua solução com vídeo pitch, repositório de código, link de demonstração e documentação.",
    },
    {
        number: "06",
        title: "Avaliação Ponderada",
        description: "Juízes designados avaliam seu projeto conforme critérios como inovação, impacto, UX e execução técnica.",
    },
    {
        number: "07",
        title: "Resultados & Reconhecimento",
        description: "Confira o ranking oficial calculado automaticamente, receba premiações e certificados validados.",
    },
];
</script>

<template>
    <Head>
        <title>Home</title>
    </Head>

    <section class="container landing-hero">
        <div class="row align-items-center g-5">
            <div class="col-lg-5 col-12">
                <blockquote class="blockquote mb-3">
                    <span class="fw-semibold fs-6">
                        “Motirõ é a reunião de pessoas colhendo ou construindo
                        algo juntos, uns ajudando os outros.”
                        <cite class="blockquote-footer"
                            >Dicionário Tupi-Guarani</cite
                        >
                    </span>
                </blockquote>

                <h1 class="display-5 fw-bold title-page mb-4">
                    A plataforma que conecta
                    <AnimateText :words="animatedWords" :color="'thirdary'" />
                    para transformar ideias em soluções reais.
                </h1>

                <p class="lead text-secondary mb-4">
                    No Motirõ, você encontra tudo para vivenciar a melhor experiência em Hackathons:
                    desde a formação de equipes até a avaliação transparente e publicação oficial de resultados.
                </p>

                
                <div class="d-flex flex-column flex-sm-row gap-3">
                    <Link
                        href="#"
                        class="btn btn-primary btn-lg fw-bold shadow-sm"
                    >
                        <font-awesome-icon :icon="['fas', 'user-astronaut']" class="me-2" />
                        Participar de Hackathons
                    </Link>

                    <Link
                        href="#"
                        class="btn btn-outline-dark btn-lg fw-bold"
                    >
                        <font-awesome-icon :icon="['fas', 'building']" class="me-2" />
                        Gerenciar Hackathons
                    </Link>
                </div>

                <div class="mt-4 pt-2 d-flex flex-wrap align-items-center gap-3 text-muted small">
                    <span><font-awesome-icon :icon="['fas', 'check']" class="text-success me-1" /> 100% Gratuito para participantes</span>
                    <span><font-awesome-icon :icon="['fas', 'check']" class="text-success me-1" /> Multi-Hackathon & Seguro</span>
                </div>
            </div>

            <div class="col-lg-7 col-12">
                <HeroImages />
            </div>
        </div>
    </section>

    <section id="sobre" class="py-5 border-top border-bottom background-img">
        <div class="container py-4">
            <div class="text-center max-w-700 mx-auto mb-5">
                <span class="fw-bold text-uppercase small tracking-wide bg-white p-2 rounded text-dark">Ecossistema Colaborativo</span>
                <h2 class="fw-bold mt-4 mb-3 text-light">O que é o Motirõ?</h2>
                <p class="text-light lead">
                    O Motirõ é uma plataforma completa projetada para centralizar todo o ciclo de vida de um Hackathon,
                    conectando cinco pilares essenciais em um ambiente intuitivo e confiável.
                </p>
            </div>

            <div class="row g-4 justify-content-center">
                <div v-for="(pillar, idx) in pillars" :key="idx" class="col-md-6 col-lg-4">
                    <div class="feature-pillar-card shadow-sm">
                        <div class="pillar-icon" :class="pillar.iconColor">
                            <font-awesome-icon :icon="pillar.icon" />
                        </div>
                        <h4 class="fw-bold mb-2">{{ pillar.title }}</h4>
                        <p class="text-secondary mb-0 small lh-base">
                            {{ pillar.description }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="organizacoes" class="py-5 bg-light border-top border-bottom">
        <div class="container py-4">
            <div class="text-center max-w-700 mx-auto mb-5">
                <span class="text-primary fw-bold text-uppercase small tracking-wide">Perfis Dedicados</span>
                <h2 class="fw-bold mt-2 mb-3">Feito Sob Medida para Você</h2>
                <p class="text-secondary lead">
                    Ambientes distintos projetados com as ferramentas certas para quem compete e para quem organiza.
                </p>
            </div>

            <div class="row g-4">
                
                <div class="col-lg-6">
                    <div class="role-highlight-card role-competitor shadow-sm d-flex flex-column">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <span class="fs-1 text-success">
                                <font-awesome-icon :icon="['fas', 'user-astronaut']" />
                            </span>
                            <div>
                                <h3 class="fw-bold mb-0 text-dark">Para Participantes</h3>
                                <span class="text-success fw-semibold small">Estudantes, Devs, Designers, Criadores</span>
                            </div>
                        </div>

                        <ul class="list-unstyled d-flex flex-column gap-2 text-secondary mb-4 flex-grow-1">
                            <li><font-awesome-icon :icon="['fas', 'circle-check']" class="text-success me-2" /> Crie e gerencie sua própria equipe ou aceite convites.</li>
                            <li><font-awesome-icon :icon="['fas', 'circle-check']" class="text-success me-2" /> Inscreva-se em múltiplos hackathons simultaneamente.</li>
                            <li><font-awesome-icon :icon="['fas', 'circle-check']" class="text-success me-2" /> Acompanhe etapas, prazos e avisos em um painel unificado.</li>
                            <li><font-awesome-icon :icon="['fas', 'circle-check']" class="text-success me-2" /> Submeta projetos com controle de versão imutável.</li>
                            <li><font-awesome-icon :icon="['fas', 'circle-check']" class="text-success me-2" /> Receba feedback detalhado dos juízes e certificados.</li>
                        </ul>

                        <Link
                            href="#"
                            class="btn btn-success btn-lg fw-bold text-white w-100 shadow-sm"
                        >
                            Quero Competir &rarr;
                        </Link>
                    </div>
                </div>

                
                <div class="col-lg-6">
                    <div class="role-highlight-card role-organization shadow-sm d-flex flex-column">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <span class="fs-1 text-primary">
                                <font-awesome-icon :icon="['fas', 'building']" />
                            </span>
                            <div>
                                <h3 class="fw-bold mb-0 text-dark">Para Organizações</h3>
                                <span class="text-primary fw-semibold small">Empresas, Universidades, Comunidades, Startups</span>
                            </div>
                        </div>

                        <ul class="list-unstyled d-flex flex-column gap-2 text-secondary mb-4 flex-grow-1">
                            <li><font-awesome-icon :icon="['fas', 'circle-check']" class="text-primary me-2" /> Crie páginas públicas com identidade da sua organização.</li>
                            <li><font-awesome-icon :icon="['fas', 'circle-check']" class="text-primary me-2" /> Configure etapas, regulamentos e limites personalizados.</li>
                            <li><font-awesome-icon :icon="['fas', 'circle-check']" class="text-primary me-2" /> Painel de moderação para aprovar ou rejeitar equipes.</li>
                            <li><font-awesome-icon :icon="['fas', 'circle-check']" class="text-primary me-2" /> Distribuição automática de projetos para bancas avaliadoras.</li>
                            <li><font-awesome-icon :icon="['fas', 'circle-check']" class="text-primary me-2" /> Cálculo automatizado de rankings com critérios ponderados.</li>
                        </ul>

                        <Link
                            href="#"
                            class="btn btn-primary btn-lg fw-bold text-white w-100 shadow-sm"
                        >
                            Quero Criar um Hackathon &rarr;
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <section id="como-funciona" class="py-5">
        <div class="container py-4">
            <div class="text-center max-w-700 mx-auto mb-5">
                <span class="text-primary fw-bold text-uppercase small tracking-wide">Passo a Passo</span>
                <h2 class="fw-bold mt-2 mb-3">Como Funciona no Motirõ?</h2>
                <p class="text-secondary lead">
                    Da descoberta do desafio até o pódio, cada etapa é acompanhada com clareza e transparência.
                </p>
            </div>

            <div class="row g-4 justify-content-center">
                <div v-for="step in steps" :key="step.number" class="col-md-6 col-lg-4">
                    <div class="step-card shadow-sm">
                        <div class="step-number bg-primary text-white">
                            {{ step.number }}
                        </div>
                        <h5 class="fw-bold mb-2">{{ step.title }}</h5>
                        <p class="text-secondary small mb-0 lh-base">
                            {{ step.description }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 text-center text-white background-img">
        <div class="container py-4">
            <h2 class="fw-bold mb-3">Pronto para dar vida a novas ideias?</h2>
            <p class="text-light text-opacity-75 lead mb-4 max-w-700 mx-auto">
                Seja resolvendo grandes desafios ou impulsionando a inovação da sua organização,
                o Motirõ é o seu ponto de encontro.
            </p>
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <Link href="#" class="btn btn-outline-light btn-lg fw-semibold">
                    Explorar Hackathons
                </Link>
                <Link href="#" class="btn btn-primary btn-lg fw-semibold">
                    Começar Gratuitamente
                </Link>
            </div>
        </div>
    </section>
</template>

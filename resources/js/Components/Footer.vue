<script setup>
import { computed } from "vue";
import { Link, usePage } from "@inertiajs/vue3";

</script>

<template>
    <footer class="main-footer pt-5">
        <div class="container-fluid px-lg-5">
            <div class="row g-4 mb-4">
                <!-- Brand / Info -->
                <div class="col-lg-3">
                    <p class="text-muted small">
                        Referência em consultoria e desenvolvimento de brindes promocionais personalizados para empresas em todo o Brasil.
                    </p>
                    <div class="d-flex gap-2 mt-3">
                        <a href="https://www.instagram.com/captabrindes/" target="_blank" class="social-icon-btn" aria-label="Instagram">
                            <font-awesome-icon :icon="['fab', 'instagram']" />
                        </a>
                        <a href="https://www.linkedin.com/company/captabrindes/" target="_blank" class="social-icon-btn" aria-label="LinkedIn">
                            <font-awesome-icon :icon="['fab', 'linkedin']" />
                        </a>
                        <a href="https://www.facebook.com/captabrindes" target="_blank" class="social-icon-btn" aria-label="Facebook">
                            <font-awesome-icon :icon="['fab', 'facebook']" />
                        </a>
                    </div>
                </div>

                <!-- Temas Populares -->
                <div class="col-lg-6">
                    
                </div>

                <!-- Atendimento -->
                <div class="col-lg-3">
                    <div class="row g-3">
                        <div class="col-12">
                            <h3 class="footer-title fw-bold text-uppercase fs-6">Central de Atendimento</h3>
                            <p class="mb-1 text-muted small">
                                <font-awesome-icon :icon="['fab', 'whatsapp']" class="text-success me-2" />
                                BA +55 (71) 98826-4777
                            </p>
                            <p class="mb-1 text-muted small">
                                <font-awesome-icon :icon="['fas', 'envelope']" class="text-primary me-2" />
                                atendimento@captabrindes.com.br
                            </p>
                            <p class="mb-0 text-muted small">
                                <font-awesome-icon :icon="['fas', 'map-marker-alt']" class="text-danger me-2" />
                                Salvador - Bahia | Atendimento Nacional
                            </p>
                        </div>
                        <div class="col-12">
                            <h3 class="footer-title fw-bold text-uppercase fs-6">Horário de Funcionamento</h3>
                            <p class="mb-0 text-muted small">
                                Segunda a sexta-feira, das 08:30 às 17:00h.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Credits -->
            <div class="border-top pt-3 mt-4 d-flex flex-column flex-sm-row justify-content-between align-items-center text-muted small">
                <p class="mb-2 mb-sm-0">&copy; {{ new Date().getFullYear() }} Motirõ. Todos os direitos reservados.</p>
                <div class="d-flex gap-3">
                    <Link href="#" class="text-muted text-decoration-none">Blog</Link>
                    <Link href="#" class="text-muted text-decoration-none">Dúvidas Frequentes</Link>
                    <Link href="#" class="text-muted text-decoration-none">Sobre Nós</Link>
                </div>
            </div>
        </div>
    </footer>
</template>

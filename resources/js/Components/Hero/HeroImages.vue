<script setup>
import Image1 from '@/assets/images/undraw/undraw_space-exploration_dhu1.svg';
import Image2 from '@/assets/images/undraw/undraw_to-the-moon_w1wa.svg';
</script>

<template>
    <div class="row">
        <div
            class="col-lg-6 d-none d-lg-flex flex-column justify-content-start align-items-center"
        >
            <div class="position-relative">
                <div class="position-absolute bottom-25 start-0 text-light">
                    <div
                        class="bg-thirdary bg-gradient rounded-pill py-2 px-3 shadow mb-2 d-inline-block"
                    >
                        <font-awesome-icon icon="fa-solid fa-trophy" />
                        <span class="fw-semibold ms-2"
                            >Premiações incriveis
                        </span>
                    </div>

                    <div
                        class="bg-thirdary bg-gradient rounded-pill py-2 px-3 shadow mb-2 d-inline-block"
                    >
                        <font-awesome-icon icon="fa-solid fa-people-group" />
                        <span class="fw-semibold ms-2"
                            >Times colaborativos</span
                        >
                    </div>

                    <div
                        class="bg-thirdary bg-gradient rounded-pill py-2 px-3 shadow d-inline-block"
                    >
                        <font-awesome-icon icon="fa-solid fa-diagram-project" />
                        <span class="fw-semibold ms-2"
                            >Projetos de impacto</span
                        >
                    </div>
                </div>

                <!-- Left background SVG -->
                <svg width="270" height="189" viewBox="0 0 145 189">
                    <path
                        d="M0 179C0 80 80 0 179 0H189C197 0 204 7 204 15V166C204 178 194 188 181 188H8C3 188 0 184 0 179Z"
                        fill="#2216CA"
                    />
                </svg>
            </div>
        </div>

        <!-- Center main student image -->
        <div class="col-lg-6 mt-8 mt-lg-0">
            <div
                class="d-flex justify-content-center rounded-4 position-relative hero-image-offset"
            >
                <img
                    :src="Image2"
                    alt=""
                    loading="lazy"
                    class="position-absolute bottom-0 img-fluid"
                />
            </div>
        </div>

        <!-- Right blue image (desktop only) -->
        <div
            class="col-lg-6 d-none d-lg-flex flex-column justify-content-end"
        >
            <div
                class="d-flex justify-content-center rounded-4 position-relative mx-5 hero-image-offset"
            >
                <img
                    :src="Image1"
                    alt=""
                    loading="lazy"
                    class="position-absolute bottom-0 img-fluid"
                />
            </div>
        </div>

        <!-- Right background SVG -->
        <div class="col-lg-6 d-none d-lg-block mt-5">
            <svg width="204" height="200" viewBox="0 0 204 177">
                <path
                    d="M204 9C204 108 123 188 24 188H15C6 188 0 181 0 173V22C0 10 8 0 20 0H188C197 0 204 7 204 9Z"
                    fill="#A659F7"
                />
            </svg>
        </div>
    </div>
</template>

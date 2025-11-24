<script setup>
import HeroFeatures from "@/js/Components/Hero/HeroFeatures.vue";
import HeroImages from "@/js/Components/Hero/HeroImages.vue";
</script>

<template>
    <section class="container">
        <div class="row align-items-center gy-6 gy-xl-0">
            <!-- Texto principal -->
            <div class="col-lg-5 col-xxl-5 col-12 mb-5 pb-5">
                <div class="d-flex flex-column gap-5">
                    <div class="d-flex flex-row gap-2 align-items-center">
                        <span>🚀</span>
                        <span class="text-primary fw-semibold">
                            Empower Your Learning Journey Today
                        </span>
                    </div>

                    <div class="d-flex flex-column gap-3">
                        <div class="d-flex flex-column gap-2">
                            <h1 class="mb-0 display-2 fw-bolder">
                                The #1 Courses Learning Platform
                            </h1>
                            <p class="mb-0">
                                Hands-on training, and certifications to help
                                you get the most from Geeks Learning.
                            </p>
                        </div>

                        <!-- Features -->
                        <HeroFeatures />
                    </div>

                    <!-- CTA Buttons -->
                    <div class="d-grid d-md-flex flex-row gap-2">
                        <a href="#" class="btn btn-primary btn-lg">
                            Join For Free
                        </a>
                        <a href="#" class="btn btn-outline-secondary btn-lg">
                            Explore Courses
                        </a>
                    </div>
                </div>
            </div>

            <!-- Imagens -->
            <div class="col-xxl-6 offset-xxl-1 col-lg-7 col-12">
                <HeroImages />
            </div>
        </div>
    </section>
</template>

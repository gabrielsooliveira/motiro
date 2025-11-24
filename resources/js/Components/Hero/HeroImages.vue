<script setup></script>

<template>
    <div class="row gx-0 gy-4 gy-lg-0">
        <!-- Left floating cards (desktop only) -->
        <div
            class="col-md-6 d-none d-md-flex flex-column justify-content-start align-items-center"
        >
            <div class="position-relative me-n7">
                <!-- Floating cards -->
                <div class="position-absolute bottom-25 start-0 ms-n8">
                    <div
                        class="bg-white rounded-pill py-2 px-3 shadow mb-2 d-inline-block"
                    >
                        <i class="bi bi-book-fill text-warning"></i>
                        <span class="text-dark fw-semibold">50+ Courses</span>
                    </div>

                    <div
                        class="bg-white rounded-pill py-2 px-3 shadow mb-2 d-inline-block"
                    >
                        <i class="bi bi-award-fill text-success"></i>
                        <span class="text-dark fw-semibold"
                            >Certified Courses</span
                        >
                    </div>

                    <div
                        class="bg-white rounded-pill py-2 px-3 shadow d-inline-block"
                    >
                        <i class="bi bi-camera-reels-fill text-danger"></i>
                        <span class="text-dark fw-semibold"
                            >Online Project</span
                        >
                    </div>
                </div>

                <!-- Left background SVG -->
                <svg width="205" height="189" viewBox="0 0 205 189">
                    <path
                        d="M0 179C0 80 80 0 179 0H189C197 0 204 7 204 15V166C204 178 194 188 181 188H8C3 188 0 184 0 179Z"
                        fill="#139A74"
                    />
                </svg>
            </div>
        </div>

        <!-- Center main student image -->
        <div class="col-md-6 mt-8 mt-md-0">
            <div
                class="bg-warning d-flex justify-content-center rounded-4 position-relative"
                style="padding-bottom: 150px; padding-top: 100px"
            >
                <img
                    src="https://geeksui.codescandy.com/geeks/assets/images/landing-immigration/college-student-holding-laptop-pointing-right.png"
                    alt=""
                    class="position-absolute bottom-0 me-8"
                />
            </div>
        </div>

        <!-- Right blue image (desktop only) -->
        <div class="col-md-6 d-none d-md-flex flex-column justify-content-end">
            <div
                class="bg-primary d-flex justify-content-center rounded-4 position-relative mx-5"
                style="padding-bottom: 150px; padding-top: 100px"
            >
                <img
                    src="https://geeksui.codescandy.com/geeks/assets/images/landing-immigration/front-view-young-man-going-university-with-bag.png"
                    alt=""
                    class="position-absolute bottom-0"
                />
            </div>
        </div>

        <!-- Right background SVG -->
        <div class="col-md-6 d-none d-md-block mt-5">
            <svg width="204" height="189" viewBox="0 0 204 189">
                <path
                    d="M204 9C204 108 123 188 24 188H15C6 188 0 181 0 173V22C0 10 8 0 20 0H188C197 0 204 7 204 9Z"
                    fill="#0d6efd"
                />
            </svg>
        </div>
    </div>
</template>

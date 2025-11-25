<script setup lang="ts">
import { ref, onMounted } from 'vue';

interface Props {
    transaction: any;
    resident: any;
    documentTypeName: string;
    content: string;
    currentDate: string;
    currentDateFormatted: string;
    officerOfTheDay?: string;
}

const props = defineProps<Props>();

const sealImagesLoaded = ref({
    municipality: false,
    barangay: false
});

const handleImageError = (event: Event) => {
    const img = event.target as HTMLImageElement;
    if (img.src.includes('municipality-tago-seal')) {
        sealImagesLoaded.value.municipality = false;
    } else if (img.src.includes('barangay-purisima-seal')) {
        sealImagesLoaded.value.barangay = false;
    }
    img.style.display = 'none';
};

const checkImageLoad = () => {
    const municipalityImg = new Image();
    municipalityImg.onload = () => {
        sealImagesLoaded.value.municipality = true;
    };
    municipalityImg.onerror = () => {
        sealImagesLoaded.value.municipality = false;
    };
    municipalityImg.src = '/images/municipality-tago-seal.png';

    const barangayImg = new Image();
    barangayImg.onload = () => {
        sealImagesLoaded.value.barangay = true;
    };
    barangayImg.onerror = () => {
        sealImagesLoaded.value.barangay = false;
    };
    barangayImg.src = '/images/barangay-purisima-seal.png';
};

onMounted(() => {
    window.print();
    checkImageLoad();
});
</script>

<template>
    <div class="certificate-container">
        <!-- Watermark (Barangay Seal) -->
        <div class="watermark">
            <img
                src="/images/barangay-purisima-seal.png"
                alt="Barangay Purisima Seal"
                class="watermark-image"
            />
        </div>

        <!-- HEADER -->
        <div class="header">
            <div class="header-row">
                <!-- Left seal -->
                <div class="seal">
                    <img
                        src="/images/municipality-tago-seal.png"
                        alt="Municipality of Tago Official Seal"
                        class="seal-image"
                        @error="handleImageError"
                    />
                    <div class="seal-fallback" v-if="!sealImagesLoaded.municipality">
                        <div class="seal-label">MUNICIPALITY</div>
                        <div class="seal-label">OF TAGO</div>
                        <div class="seal-label">OFFICIAL SEAL</div>
                    </div>
                </div>

                <!-- Center letterhead -->
                <div class="letterhead">
                    <div class="letterhead-line">Republic of the Philippines</div>
                    <div class="letterhead-line">Province of Surigao del Sur</div>
                    <div class="letterhead-line">Municipality of Tago</div>
                    <div class="letterhead-line letterhead-bold">BARANGAY PURISIMA</div>
                    <div class="o0o">-o0o-</div>
                    <div class="letterhead-office">Office of the Punong Barangay</div>
                </div>

                <!-- Right seal -->
                <div class="seal">
                    <img
                        src="/images/barangay-purisima-seal.png"
                        alt="Barangay Purisima Seal"
                        class="seal-image"
                        @error="handleImageError"
                    />
                    <div class="seal-fallback" v-if="!sealImagesLoaded.barangay">
                        <div class="seal-label">BARANGAY PURISIMA</div>
                        <div class="seal-label">TAGO, SURIGAO</div>
                        <div class="seal-label">DEL SUR 1963</div>
                    </div>
                </div>
            </div>

            <!-- Tri-color line (blue-red-blue) -->
            <div class="tricolor-line"></div>

            <!-- Main title -->
            <div class="main-title">
                {{ documentTypeName.toUpperCase() }}
            </div>
        </div>

        <!-- BODY -->
        <div class="body-content" v-html="content"></div>

        <!-- SIGNATURES -->
        <div class="signatures-section">
            <div
                v-if="props.officerOfTheDay"
                class="signature-block signature-left"
            >
                <div class="signature-name">
                    {{ props.officerOfTheDay.toUpperCase() }}
                </div>
                <div class="signature-title">Sangguniang Barangay Member</div>
                <div class="signature-subtitle">Officer of the Day</div>
            </div>

            <div
                class="signature-block signature-right"
                :class="{ 'signature-right-full': !props.officerOfTheDay }"
            >
                <div class="signature-name">EMMANUEL P. ISIANG</div>
                <div class="signature-title">Punong Barangay</div>
                <div class="signature-subtitle">
                    President – <span class="signature-red">Liga ng mga Barangay</span>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.certificate-container {
    position: relative;
    width: 8.5in;
    min-height: 11in;
    margin: 0 auto;
    padding: 0.75in 1in;
    background: #ffffff;
    font-family: 'Times New Roman', serif;
    color: #000;
    box-sizing: border-box;
    overflow: hidden;
}

/* WATERMARK */
.watermark {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: none;
    z-index: 0;
}

.watermark-image {
    max-width: 500px;
    width: 70%;
    opacity: 0.06;
}

/* HEADER */
.header {
    position: relative;
    z-index: 1;
    margin-bottom: 25px;
}

.header-row {
    display: grid;
    grid-template-columns: 120px 1fr 120px;
    align-items: center;
}

/* Seals */
.seal {
    width: 110px;
    height: 110px;
    border-radius: 50%;
    border: 2px solid #000;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.seal-image {
    width: 100%;
    height: 100%;
    object-fit: contain;
    border-radius: 50%;
}

.seal-fallback {
    position: absolute;
    text-align: center;
    padding: 8px;
}

.seal-label {
    font-size: 9px;
    font-weight: bold;
}

/* Letterhead */
.letterhead {
    text-align: center;
}

.letterhead-line {
    font-size: 13px;
    line-height: 1.3;
}

.letterhead-bold {
    font-weight: bold;
    font-size: 14px;
}

.o0o {
    margin-top: 4px;
    font-size: 12px;
}

.letterhead-office {
    margin-top: 4px;
    font-size: 14px;
    font-style: italic;
    font-family: 'Brush Script MT', 'Lucida Handwriting', cursive;
}

/* Tri-color line */
.tricolor-line {
    margin: 10px 0 20px 0;
    height: 4px;
    background: linear-gradient(
        to right,
        #005cbf 0,
        #005cbf 33.3%,
        #cc0000 33.3%,
        #cc0000 66.6%,
        #005cbf 66.6%,
        #005cbf 100%
    );
}

/* Main Title */
.main-title {
    text-align: center;
    font-size: 20px;
    font-weight: bold;
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-bottom: 20px;
}

/* BODY CONTENT */
.body-content {
    position: relative;
    z-index: 1;
    font-size: 13px;
    line-height: 1.9;
    text-align: justify;
}

.body-content :deep(p) {
    margin: 10px 0;
}

.body-content :deep(b),
.body-content :deep(strong) {
    font-weight: bold;
}

/* SIGNATURES */
.signatures-section {
    position: relative;
    z-index: 1;
    margin-top: 60px;
    display: flex;
    justify-content: flex-end;
    gap: 80px;
}

.signature-block {
    display: flex;
    flex-direction: column;
}

.signature-left {
    text-align: left;
}

.signature-right {
    text-align: right;
}

.signature-right-full {
    text-align: right;
    width: 260px;
}

.signature-name {
    font-weight: bold;
    font-size: 13px;
    margin-bottom: 4px;
}

.signature-title {
    font-size: 11px;
    margin-bottom: 2px;
}

.signature-subtitle {
    font-size: 10px;
}

.signature-red {
    color: #cc0000;
    text-decoration: underline;
}

/* PRINT */
@media print {
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    @page {
        size: A4 portrait;
        margin: 0;
    }

    body {
        margin: 0;
        padding: 0;
        background: #fff;
    }

    .certificate-container {
        width: 100%;
        margin: 0;
        padding: 0.7in 0.9in;
        box-shadow: none;
    }
}

/* SCREEN */
@media screen {
    .certificate-container {
        margin: 20px auto;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.12);
    }
}
</style>

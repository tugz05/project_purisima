<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';

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

// Check if this is a Barangay Clearance
const isBarangayClearance = computed(() => {
    const docName = props.documentTypeName?.toLowerCase() || '';
    return docName.includes('barangay clearance') || docName === 'barangay clearance';
});

// Get current year
const currentYear = computed(() => {
    return new Date().getFullYear().toString();
});

// Barangay officials list
const barangayOfficials = [
    { name: 'HON. EMMANUEL P. ISIANG', position: 'Barangay Captain' },
    { name: 'Hon. Jose M. Ihapon, Jr.', position: 'Barangay Kagawad' },
    { name: 'Hon. Charlita G. Montenegro', position: 'Barangay Kagawad' },
    { name: 'Hon. Jerusalem T. Montero', position: 'Barangay Kagawad' },
    { name: 'Hon. Jane C. Buniel', position: 'Barangay Kagawad' },
    { name: 'Hon. Eva M. Gemao', position: 'Barangay Kagawad' },
    { name: 'Hon. John Earl J. Elizalde', position: 'Barangay Kagawad' },
    { name: 'Hon. Vicente A. Portillo, Jr.', position: 'Barangay Kagawad' },
    { name: 'Hon. Vince Dylan E. Curada', position: 'Sangguniang Kabataan' },
    { name: 'Shannon James Q. Porras', position: 'Barangay Secretary' },
    { name: 'Heries B Dela Cruz', position: 'Barangay Treasurer' },
];

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
    checkImageLoad();
    setTimeout(() => {
        window.print();
    }, 500);
});
</script>

<template>
    <!-- BARANGAY CLEARANCE DESIGN -->
    <div v-if="isBarangayClearance" class="clearance-container">
        <!-- Watermark -->
        <div class="clearance-watermark">
            <img
                src="/images/barangay-purisima-seal.png"
                alt="Barangay Purisima Seal"
                class="clearance-watermark-image"
            />
        </div>

        <!-- HEADER -->
        <div class="clearance-header">
            <div class="clearance-header-row">
                <!-- Left seal -->
                <div class="clearance-seal clearance-seal-left">
                    <img
                        src="/images/municipality-tago-seal.png"
                        alt="Municipality of Tago Official Seal"
                        class="clearance-seal-image"
                        @error="handleImageError"
                    />
                    <div class="clearance-seal-fallback" v-if="!sealImagesLoaded.municipality">
                        <div class="clearance-seal-label">MUNICIPALITY</div>
                        <div class="clearance-seal-label">OF TAGO</div>
                        <div class="clearance-seal-label">OFFICIAL SEAL</div>
                    </div>
                </div>

                <!-- Center letterhead -->
                <div class="clearance-letterhead">
                    <div class="clearance-letterhead-line">Republic of the Philippines</div>
                    <div class="clearance-letterhead-line">Province of Surigao del Sur</div>
                    <div class="clearance-letterhead-line">Municipality of Tago</div>
                    <div class="clearance-letterhead-line clearance-letterhead-bold">Barangay Purisima</div>
                    <div class="clearance-letterhead-office">Office of the Barangay Captain</div>
                </div>

                <!-- Right seal -->
                <div class="clearance-seal clearance-seal-right">
                    <img
                        src="/images/barangay-purisima-seal.png"
                        alt="Barangay Purisima Seal"
                        class="clearance-seal-image"
                        @error="handleImageError"
                    />
                    <div class="clearance-seal-fallback" v-if="!sealImagesLoaded.barangay">
                        <div class="clearance-seal-label">BARANGAY PURISIMA</div>
                        <div class="clearance-letterhead-line">TAGO, SURIGAO</div>
                        <div class="clearance-seal-label">DEL SUR 1963</div>
                    </div>
                </div>
            </div>

            <!-- Main title -->
            <div class="clearance-main-title">
                {{ documentTypeName.toUpperCase() }}
            </div>
        </div>

        <!-- MAIN CONTENT WITH SIDEBAR -->
        <div class="clearance-content-wrapper">
            <!-- LEFT SIDEBAR -->
            <div class="clearance-sidebar">
                <div class="clearance-year">{{ currentYear }}</div>
                <div class="clearance-officials">
                    <div
                        v-for="(official, index) in barangayOfficials"
                        :key="index"
                        class="clearance-official-item"
                    >
                        <div class="clearance-official-name">{{ official.name }}</div>
                        <div class="clearance-official-position">{{ official.position }}</div>
                    </div>
                </div>
            </div>

            <!-- RIGHT MAIN CONTENT -->
            <div class="clearance-main-content">
                <div class="clearance-body-content">
                    <p class="clearance-salutation">TO WHOM IT MAY CONCERN:</p>
                    <div v-html="content"></div>
                </div>

                <!-- Signature Section -->
                <div class="clearance-signatures">
                    <div class="clearance-signature-left">
                        <div class="clearance-signature-line"></div>
                        <div class="clearance-signature-label">SIGNATURE OF SUBJECT PERSON</div>
                    </div>
                    <div class="clearance-signature-right">
                        <div class="clearance-signature-name">EMMANUEL P. ISIANG</div>
                        <div class="clearance-signature-title">Punong Barangay</div>
                    </div>
                </div>

                <!-- Footer Note -->
                <div class="clearance-footer-note">
                    <div class="clearance-footer-box">
                        NOT VALID WITHOUT OFFICIAL RECEIPT AND<br>
                        OFFICIAL SEAL. NON-TRANSFERABLE<br>
                        (Valid only for six (6) months from date of issue)
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- STANDARD CERTIFICATION DESIGN -->
    <div v-else class="certificate-container">
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
                <div class="seal seal-left">
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
                    <div class="o0o">-oOo-</div>
                    <div class="letterhead-office">Office of the Punong Barangay</div>
                </div>

                <!-- Right seal -->
                <div class="seal seal-right">
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

            <!-- Decorative line with center element -->
            <div class="decorative-line">
                <div class="line-left"></div>
                <div class="line-center"></div>
                <div class="line-right"></div>
            </div>

            <!-- Main title -->
            <div class="main-title">
                {{ documentTypeName.toUpperCase() }}
            </div>
        </div>

        <!-- BODY -->
        <div class="body-content">
            <p class="salutation">TO WHOM IT MAY CONCERN:</p>
            <div v-html="content"></div>
        </div>

        <!-- SIGNATURES -->
        <div class="signatures-section">
            <div class="signatures-container">
                <!-- Left: Officer of the Day (if present) -->
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

                <!-- Right: Punong Barangay -->
                <div class="signature-block signature-right">
                    <div class="signature-name">EMMANUEL P. ISIANG</div>
                    <div class="signature-title">Punong Barangay</div>
                    <div class="signature-subtitle">
                        President – <span class="signature-red">Liga ng mga Barangay</span>
                    </div>
                    <div class="signature-authority" v-if="props.officerOfTheDay">
                        For and by the authority of the Punong Barangay
                    </div>
                </div>
            </div>
        </div>

        <!-- FOOTER -->
        <div class="footer">
            <div class="footer-text">Not valid without official seal</div>
        </div>
    </div>
</template>

<style scoped>
/* ============================================
   STANDARD CERTIFICATION STYLES
   ============================================ */
.certificate-container {
    position: relative;
    width: 8.5in;
    min-height: 11in;
    margin: 0 auto;
    padding: 0.6in 0.9in;
    background: #ffffff;
    font-family: 'Times New Roman', serif;
    color: #000;
    box-sizing: border-box;
    overflow: hidden;
}

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
    max-width: 550px;
    width: 75%;
    opacity: 0.06;
    object-fit: contain;
}

.header {
    position: relative;
    z-index: 1;
    margin-bottom: 30px;
}

.header-row {
    display: grid;
    grid-template-columns: 130px 1fr 130px;
    align-items: center;
    gap: 15px;
    margin-bottom: 12px;
}

.seal {
    width: 130px;
    height: 130px;
    border-radius: 50%;
    border: none;
    background: transparent;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
    flex-shrink: 0;
}

.seal-image {
    width: 130px;
    height: 130px;
    object-fit: contain;
    border-radius: 50%;
}

.seal-fallback {
    position: absolute;
    text-align: center;
    padding: 10px;
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

.seal-label {
    font-size: 9px;
    font-weight: bold;
    line-height: 1.2;
}

.letterhead {
    text-align: center;
    flex: 1;
}

.letterhead-line {
    font-size: 16px;
    line-height: 1.4;
    font-weight: normal;
}

.letterhead-bold {
    font-weight: bold;
    font-size: 18px;
    letter-spacing: 0.5px;
}

.o0o {
    margin-top: 5px;
    margin-bottom: 5px;
    font-size: 12px;
    font-weight: normal;
}

.letterhead-office {
    margin-top: 6px;
    font-size: 15px;
    font-style: italic;
    font-family: 'Brush Script MT', 'Lucida Handwriting', 'Brush Script', cursive;
    font-weight: normal;
}

.decorative-line {
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 12px 0 25px 0;
    position: relative;
    height: 1px;
}

.line-left,
.line-right {
    flex: 1;
    height: 1px;
    background: #000;
}

.line-center {
    width: 8px;
    height: 8px;
    background: #000;
    border-radius: 50%;
    margin: 0 8px;
    flex-shrink: 0;
}

.main-title {
    text-align: center;
    font-size: 22px;
    font-weight: bold;
    letter-spacing: 2px;
    text-transform: uppercase;
    margin-bottom: 25px;
    font-family: 'Times New Roman', serif;
}

.body-content {
    position: relative;
    z-index: 1;
    font-size: 15px;
    line-height: 1.8;
    text-align: justify;
    margin-bottom: 20px;
}

.salutation {
    font-weight: bold;
    margin-bottom: 12px;
    text-indent: 0;
}

.body-content :deep(p) {
    margin: 12px 0;
    text-indent: 2em;
}

.body-content :deep(b),
.body-content :deep(strong) {
    font-weight: bold;
}

.signatures-section {
    position: relative;
    z-index: 1;
    margin-top: 50px;
    margin-bottom: 30px;
}

.signatures-container {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 60px;
}

.signature-block {
    display: flex;
    flex-direction: column;
    flex: 1;
    max-width: 280px;
}

.signature-left {
    text-align: left;
}

.signature-right {
    text-align: right;
}

.signature-name {
    font-weight: bold;
    font-size: 13px;
    margin-bottom: 5px;
    letter-spacing: 0.5px;
    line-height: 1.3;
}

.signature-title {
    font-size: 11px;
    margin-bottom: 3px;
    line-height: 1.3;
}

.signature-subtitle {
    font-size: 10px;
    line-height: 1.3;
}

.signature-authority {
    font-size: 9px;
    font-style: italic;
    margin-top: 8px;
    line-height: 1.3;
}

.signature-red {
    color: #cc0000;
    text-decoration: underline;
}

.footer {
    position: relative;
    z-index: 1;
    margin-top: 40px;
    padding-top: 15px;
}

.footer-text {
    font-size: 9px;
    font-style: italic;
    color: #333;
}

/* ============================================
   BARANGAY CLEARANCE STYLES
   ============================================ */
.clearance-container {
    position: relative;
    width: 8.5in;
    min-height: 11in;
    margin: 0 auto;
    padding: 0;
    background: #ffffff;
    font-family: Arial, sans-serif;
    color: #000;
    box-sizing: border-box;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.clearance-watermark {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: none;
    z-index: 0;
}

.clearance-watermark-image {
    max-width: 200px;
    width: 30%;
    opacity: 0.08;
    object-fit: contain;
}

.clearance-header {
    position: relative;
    z-index: 1;
    padding: 0.4in 0.5in 0.3in 0.5in;
    background: #fff;
}

.clearance-header-row {
    display: grid;
    grid-template-columns: 130px 1fr 130px;
    align-items: center;
    gap: 12px;
    margin-bottom: 15px;
}

.clearance-seal {
    width: 130px;
    height: 130px;
    border-radius: 50%;
    border: none;
    background: transparent;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
    flex-shrink: 0;
}

.clearance-seal-image {
    width: 130px;
    height: 130px;
    object-fit: contain;
    border-radius: 50%;
}

.clearance-seal-fallback {
    position: absolute;
    text-align: center;
    padding: 8px;
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

.clearance-seal-label {
    font-size: 8px;
    font-weight: bold;
    line-height: 1.1;
}

.clearance-letterhead {
    text-align: center;
    flex: 1;
}

.clearance-letterhead-line {
    font-size: 15px;
    line-height: 1.3;
    font-weight: normal;
    font-family: 'Times New Roman', serif;
}

.clearance-letterhead-bold {
    font-weight: bold;
    font-size: 17px;
    letter-spacing: 0.3px;
}

.clearance-letterhead-office {
    margin-top: 4px;
    font-size: 13px;
    font-style: italic;
    font-family: 'Brush Script MT', 'Lucida Handwriting', 'Brush Script', cursive;
    font-weight: normal;
}

.clearance-main-title {
    text-align: center;
    font-size: 24px;
    font-weight: bold;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: #005cbf;
    margin-top: 10px;
    font-family: Arial, sans-serif;
}

.clearance-content-wrapper {
    position: relative;
    z-index: 1;
    display: flex;
    flex: 1;
    min-height: 0;
}

.clearance-sidebar {
    width: 1.8in;
    background: linear-gradient(180deg, #ffa500 0%, #ff8c00 100%);
    padding: 0.3in 0.2in;
    color: #000;
    font-family: Arial, sans-serif;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.clearance-year {
    font-size: 72px;
    font-weight: bold;
    color: #005cbf;
    line-height: 1;
    margin-bottom: 20px;
    font-family: Arial, sans-serif;
}

.clearance-officials {
    width: 100%;
}

.clearance-official-item {
    margin-bottom: 8px;
}

.clearance-official-name {
    font-size: 9px;
    font-weight: bold;
    line-height: 1.2;
    margin-bottom: 2px;
}

.clearance-official-position {
    font-size: 8px;
    line-height: 1.2;
    color: #333;
}

.clearance-main-content {
    flex: 1;
    padding: 0.3in 0.5in;
    background: #fff;
    position: relative;
}

.clearance-body-content {
    font-size: 14px;
    line-height: 1.6;
    text-align: justify;
    font-family: Arial, sans-serif;
    margin-bottom: 20px;
}

.clearance-salutation {
    font-weight: bold;
    margin-bottom: 10px;
    text-indent: 0;
}

.clearance-body-content :deep(p) {
    margin: 8px 0;
    text-indent: 2em;
}

.clearance-body-content :deep(b),
.clearance-body-content :deep(strong) {
    font-weight: bold;
}

.clearance-signatures {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-top: 30px;
    margin-bottom: 15px;
}

.clearance-signature-left {
    flex: 1;
    max-width: 200px;
}

.clearance-signature-line {
    border-top: 1px solid #000;
    margin-bottom: 5px;
    width: 100%;
}

.clearance-signature-label {
    font-size: 9px;
    text-align: center;
    color: #333;
}

.clearance-signature-right {
    text-align: right;
    flex: 1;
    max-width: 200px;
}

.clearance-signature-name {
    font-weight: bold;
    font-size: 12px;
    margin-bottom: 3px;
    letter-spacing: 0.5px;
}

.clearance-signature-title {
    font-size: 10px;
}

.clearance-footer-note {
    margin-top: 15px;
}

.clearance-footer-box {
    background: #ff0000;
    color: #fff;
    padding: 8px 12px;
    font-size: 9px;
    font-weight: bold;
    text-align: center;
    line-height: 1.4;
    border: 1px solid #000;
}

/* PRINT STYLES */
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

    .certificate-container,
    .clearance-container {
        width: 100%;
        margin: 0;
        padding: 0.6in 0.9in;
        box-shadow: none;
        page-break-after: avoid;
    }

    .clearance-container {
        padding: 0;
    }

    .clearance-header {
        padding: 0.4in 0.5in 0.3in 0.5in;
    }

    .clearance-sidebar {
        width: 1.8in;
        padding: 0.3in 0.2in;
    }

    .clearance-main-content {
        padding: 0.3in 0.5in;
    }

    .header,
    .body-content,
    .signatures-section,
    .clearance-header,
    .clearance-content-wrapper {
        page-break-inside: avoid;
    }
}

/* SCREEN STYLES */
@media screen {
    .certificate-container,
    .clearance-container {
        margin: 20px auto;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.12);
    }
}
</style>

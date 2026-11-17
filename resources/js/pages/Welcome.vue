<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    ArrowRight,
    AtSign,
    Camera,
    Check,
    ChevronDown,
    ChevronUp,
    Download,
    Globe,
    Image as ImageIcon,
    Menu,
    MessageCircle,
    Play,
    ShieldCheck,
    Smartphone,
    Sparkles,
    Star,
    Video,
    X,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue';
import type {
    BannerPayload,
    CatalogProduct,
    CategoryPayload,
    FaqItem,
    LatestAppVersion,
    ProductPayload,
    StoreSettingPayload,
    TryOnExample,
} from '@/lib/landingDummyData';
import {
    catalogProducts,
    DEFAULT_WHATSAPP_MESSAGE,
    DEFAULT_WHATSAPP_NUMBER,
    faqItems,
    mapProduct,
    tryOnExamples,
} from '@/lib/landingDummyData';

const props = withDefaults(
    defineProps<{
        settings?: StoreSettingPayload | null;
        banners?: BannerPayload[];
        faqs?: FaqItem[];
        categories?: CategoryPayload[];
        products?: ProductPayload[];
        latestApps?: {
            android: LatestAppVersion | null;
            ios: LatestAppVersion | null;
        };
        siteUrl?: string;
    }>(),
    {
        settings: null,
        banners: () => [],
        faqs: () => [],
        categories: () => [],
        products: () => [],
        latestApps: () => ({ android: null, ios: null }),
        siteUrl: '',
    },
);

const canonicalUrl = computed(() => props.siteUrl || 'https://arbatik.id/');
const logoUrl = '/images/logo.png';

const faqs = computed<FaqItem[]>(() =>
    props.faqs && props.faqs.length > 0 ? props.faqs : faqItems,
);

const products = computed<CatalogProduct[]>(() =>
    props.products && props.products.length > 0
        ? props.products.map(mapProduct)
        : catalogProducts,
);

const categories = computed<CategoryPayload[]>(() => props.categories ?? []);
const settings = computed<StoreSettingPayload | null>(() => props.settings ?? null);
const banner = computed<BannerPayload | null>(() => props.banners?.[0] ?? null);

const waUrl = computed(() => {
    const number = settings.value?.contact?.whatsapp || DEFAULT_WHATSAPP_NUMBER;
    const phone = number.replace(/[^0-9]/g, '');
    const text = encodeURIComponent(DEFAULT_WHATSAPP_MESSAGE);

    return `https://wa.me/${phone}?text=${text}`;
});

// Mobile Navigation State
const mobileMenuOpen = ref(false);

const latestApps = reactive<Record<'android' | 'ios', LatestAppVersion | null>>({
    android: props.latestApps?.android ?? null,
    ios: props.latestApps?.ios ?? null,
});

const isCheckingDownload = ref(false);
const downloadModalOpen = ref(false);
const selectedPlatform = ref<'android' | 'ios'>('android');
const modalCustomMessage = ref<string | null>(null);

function formatBytes(bytes: number | null): string {
    if (!bytes || bytes <= 0) {
        return '';
    }

    const mb = bytes / (1024 * 1024);

    return mb >= 1 ? `${mb.toFixed(1)} MB` : `${(bytes / 1024).toFixed(0)} KB`;
}

async function fetchLatestApp(platform: 'android' | 'ios'): Promise<LatestAppVersion | null> {
    try {
        const res = await fetch(`/api/v1/app-versions/latest?type=${platform}`, {
            headers: { Accept: 'application/json' },
        });

        if (!res.ok) {
            return null;
        }

        const json = await res.json();
        const data: LatestAppVersion | null = json?.data ?? null;
        latestApps[platform] = data;

        return data;
    } catch {
        return null;
    }
}

async function handleDownloadClick(platform: 'android' | 'ios' = 'android'): Promise<void> {
    selectedPlatform.value = platform;
    mobileMenuOpen.value = false;
    modalCustomMessage.value = null;

    isCheckingDownload.value = true;
    const appInfo = latestApps[platform] ?? (await fetchLatestApp(platform));
    isCheckingDownload.value = false;

    if (appInfo?.is_maintenance) {
        modalCustomMessage.value =
            appInfo.maintenance_message ||
            'Aplikasi sedang dalam pemeliharaan rutin. Silakan coba beberapa saat lagi.';
        downloadModalOpen.value = true;

        return;
    }

    const resolvedUrl = appInfo?.download_url || appInfo?.file_url;

    if (resolvedUrl) {
        window.location.href = resolvedUrl;

        return;
    }

    downloadModalOpen.value = true;
}

// Virtual Try-On Showcase State (statis langsung tanpa fetch API)
const tryOns: TryOnExample[] = tryOnExamples;

const selectedExampleId = ref<string>(tryOns[0]?.id ?? 'contoh-1');
const selectedExample = computed<TryOnExample>(
    () => tryOns.find((item) => item.id === selectedExampleId.value) ?? tryOns[0],
);
const heroPreviewMode = ref<'photo' | 'video'>('photo');

// Lightbox for try-on showcase cards
type LightboxMedia = {
    type: 'image' | 'video';
    src: string;
    poster?: string;
    title: string;
};

const lightbox = ref<LightboxMedia | null>(null);

function openLightbox(media: LightboxMedia): void {
    lightbox.value = media;
}

function closeLightbox(): void {
    lightbox.value = null;
}

function openVideoCardLightbox(): void {
    const item = selectedExample.value;

    if (item.resultVideoUrl) {
        openLightbox({
            type: 'video',
            src: item.resultVideoUrl,
            poster: item.resultVideoPoster,
            title: `Hasil Video — ${item.productName}`,
        });
    } else {
        openLightbox({
            type: 'image',
            src: item.resultVideoPoster || item.resultPhoto,
            title: `Hasil Video — ${item.productName}`,
        });
    }
}

// Catalog Filtering State
const activeCategory = ref<string>('Semua');

const categoryOptions = computed<string[]>(() => {
    if (categories.value.length > 0) {
        return ['Semua', ...categories.value.map((c) => c.name)];
    }

    const derivedNames = Array.from(
        new Set(products.value.map((p) => p.category).filter(Boolean)),
    );

    return ['Semua', ...derivedNames];
});

const filteredProducts = computed<CatalogProduct[]>(() =>
    activeCategory.value === 'Semua'
        ? products.value
        : products.value.filter((item) => (item.category ?? '') === activeCategory.value),
);

// FAQ Accordion State
const openFaqIndex = ref<number | null>(0);

// Structured Data JSON-LD for SEO
const jsonLdSchema = computed(() =>
    JSON.stringify({
        '@context': 'https://schema.org',
        '@graph': [
            {
                '@type': 'OnlineStore',
                '@id': `${canonicalUrl.value}#organization`,
                name: settings.value?.name || 'Arbatik Heritage Indonesia',
                url: canonicalUrl.value,
                description:
                    'Toko Online Baju Batik Nusantara dengan fitur Virtual Try-On. Unggah foto Anda, pilih produk batik, dan lihat hasilnya dalam bentuk foto maupun video 5 detik.',
                sameAs: ['https://instagram.com/arbatik.id', 'https://tiktok.com/@arbatik.id'],
            },
            {
                '@type': 'MobileApplication',
                name: 'Arbatik — Belanja Batik & Virtual Try-On',
                operatingSystem: 'Android, iOS',
                applicationCategory: 'ShoppingApplication',
                offers: {
                    '@type': 'Offer',
                    price: '0',
                    priceCurrency: 'IDR',
                },
                aggregateRating: {
                    '@type': 'AggregateRating',
                    ratingValue: '4.9',
                    ratingCount: '1240',
                },
                description:
                    'Aplikasi belanja baju batik modern dan tradisional dengan fitur Virtual Try-On yang memadukan foto Anda dan foto produk batik menjadi hasil foto atau video 5 detik.',
            },
            {
                '@type': 'FAQPage',
                mainEntity: faqs.value.map((f) => ({
                    '@type': 'Question',
                    name: f.question,
                    acceptedAnswer: {
                        '@type': 'Answer',
                        text: f.answer,
                    },
                })),
            },
        ],
    }),
);

function handleWindowKeydown(e: KeyboardEvent): void {
    if (e.key === 'Escape' && lightbox.value) {
        closeLightbox();
    }
}

onMounted(() => {
    document.documentElement.classList.add('scroll-smooth');
    window.addEventListener('keydown', handleWindowKeydown);

    if (!latestApps.android) {
        fetchLatestApp('android');
    }

    if (!latestApps.ios) {
        fetchLatestApp('ios');
    }
});

onUnmounted(() => {
    document.documentElement.classList.remove('scroll-smooth');
    window.removeEventListener('keydown', handleWindowKeydown);
});
</script>

<template>
    <div class="arbatik-landing">
        <Head title="Arbatik — Toko Online Baju Batik & Virtual Try-On (Foto & Video 5 Detik)">
            <meta name="text-scale" content="scale" />
            <meta name="theme-color" content="#704F38" />
            <link rel="icon" :href="logoUrl" type="image/png" />
            <link rel="manifest" href="/manifest.webmanifest" />
            <link rel="preconnect" href="https://fonts.googleapis.com" />
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
            <link
                href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
                rel="stylesheet"
            />
            <meta
                name="description"
                content="Belanja baju batik tulis & cap autentik Nusantara lebih yakin cocok! Upload foto Anda, pilih produk batik, dan lihat hasil Virtual Try-On berupa foto atau video 5 detik di aplikasi Arbatik."
            />
            <meta
                name="keywords"
                content="toko online batik, baju batik modern, virtual try on batik, coba baju batik online, video try on batik, batik sogan solo, kemeja batik pria, blouse batik wanita, aplikasi belanja batik, arbatik"
            />
            <meta name="author" content="Arbatik Indonesia" />
            <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1" />
            <link rel="canonical" :href="canonicalUrl" />

            <!-- Open Graph / Facebook / WhatsApp -->
            <meta property="og:type" content="website" />
            <meta property="og:locale" content="id_ID" />
            <meta property="og:site_name" content="Arbatik Heritage" />
            <meta
                property="og:title"
                content="Arbatik — Toko Online Baju Batik & Virtual Try-On (Foto & Video 5 Detik)"
            />
            <meta
                property="og:description"
                content="Upload foto Anda dan pilih produk batik favorit. Aplikasi kami menampilkan Anda mengenakan baju batik tersebut dalam bentuk foto maupun video 5 detik!"
            />
            <meta property="og:url" :content="canonicalUrl" />
            <meta property="og:image" :content="logoUrl" />

            <!-- Twitter Card -->
            <meta name="twitter:card" content="summary_large_image" />
            <meta
                name="twitter:title"
                content="Arbatik — Belanja Baju Batik dengan Virtual Try-On Foto & Video 5 Detik"
            />
            <meta
                name="twitter:description"
                content="Coba kecocokan baju batik cukup dengan upload foto diri Anda di aplikasi Arbatik. Lihat hasilnya dalam bentuk foto atau video 5 detik!"
            />

            <!-- JSON-LD Structured Data -->
            <component :is="'script'" type="application/ld+json">{{ jsonLdSchema }}</component>
        </Head>

        <!-- Top Announcement Banner -->
        <div class="announcement-bar">
            <div class="container announcement-inner">
                <span class="announcement-badge">INFO</span>
                <p>
                    <template v-if="banner">
                        {{ banner.title
                        }}<strong v-if="banner.description"> — {{ banner.description }}</strong>
                    </template>
                    <template v-else>
                        Coba baju batik pakai foto kamu sendiri — hasil
                        <strong>Foto &amp; Video 5 Detik</strong> di aplikasi Arbatik.
                    </template>
                </p>
                <a href="#download" class="announcement-link">
                    <span>Unduh Aplikasi</span>
                    <ArrowRight :size="14" />
                </a>
            </div>
        </div>

        <!-- Sticky Navigation Header -->
        <header class="site-header">
            <div class="container header-inner">
                <a href="#beranda" class="brand-logo" aria-label="Beranda Arbatik">
                    <img :src="logoUrl" alt="ARBatik — Try It On You" class="brand-logo-img" />
                </a>

                <nav class="desktop-nav" aria-label="Navigasi Utama">
                    <a href="#virtual-try-on">Contoh Try-On</a>
                    <a href="#fitur">Keunggulan</a>
                    <a href="#katalog">Koleksi Batik</a>
                    <a href="#cara-kerja">Cara Belanja</a>
                    <a href="#faq">FAQ</a>
                </nav>

                <div class="header-actions">
                    <button
                        type="button"
                        class="btn-primary-cta"
                        @click="handleDownloadClick('android')"
                    >
                        <Download :size="16" />
                        <span>Download Aplikasi</span>
                    </button>

                    <!-- Mobile Hamburger Button -->
                    <button
                        type="button"
                        class="mobile-menu-toggle"
                        :aria-label="mobileMenuOpen ? 'Tutup menu navigasi' : 'Buka menu navigasi'"
                        :aria-expanded="mobileMenuOpen"
                        @click="mobileMenuOpen = !mobileMenuOpen"
                    >
                        <X v-if="mobileMenuOpen" :size="22" />
                        <Menu v-else :size="22" />
                    </button>
                </div>
            </div>

            <!-- Mobile Navigation Drawer -->
            <div v-if="mobileMenuOpen" class="mobile-drawer">
                <nav class="mobile-drawer-nav" aria-label="Navigasi Mobile">
                    <a href="#virtual-try-on" @click="mobileMenuOpen = false">
                        Contoh Hasil Try-On
                    </a>
                    <a href="#fitur" @click="mobileMenuOpen = false">Keunggulan Toko Arbatik</a>
                    <a href="#katalog" @click="mobileMenuOpen = false">Katalog Koleksi Batik</a>
                    <a href="#cara-kerja" @click="mobileMenuOpen = false">Cara Belanja</a>
                    <a href="#faq" @click="mobileMenuOpen = false">Pertanyaan Umum (FAQ)</a>
                </nav>
                <div class="mobile-drawer-cta">
                    <button
                        type="button"
                        class="btn-primary-cta full-width"
                        @click="handleDownloadClick('android')"
                    >
                        <Download :size="17" />
                        <span>Download Aplikasi Mobile</span>
                    </button>
                </div>
            </div>
        </header>

        <main>
            <!-- HERO SECTION -->
            <section id="beranda" class="hero-section">
                <div class="container hero-grid">
                    <!-- Left Column: Copy & Download Actions -->
                    <div class="hero-content">
                        <div class="hero-pill">
                            <span>Toko Online Busana Batik Nusantara</span>
                        </div>

                        <h1 class="hero-title">
                            Coba Baju Batik Pakai
                            <span class="text-serif-italic">Foto Kamu</span> Sebelum Membeli.
                        </h1>

                        <p class="hero-subtitle">
                            Belanja batik tulis dan cap pilihan kini lebih tenang. Unggah foto diri
                            Anda (atau pilih foto model) dan pilih produk batik favorit di aplikasi
                            <strong>Arbatik</strong> untuk melihat hasilnya dalam bentuk
                            <strong>Foto</strong> maupun <strong>Video 5 Detik</strong>.
                        </p>

                        <div class="hero-cta-group">
                            <button
                                type="button"
                                class="btn-solid-primary"
                                @click="handleDownloadClick('android')"
                            >
                                <Download :size="19" />
                                <span>Download Aplikasi (Android)</span>
                            </button>

                            <a href="#virtual-try-on" class="btn-solid-outline">
                                <Play :size="17" />
                                <span>Lihat Contoh Hasil Try-On</span>
                            </a>
                        </div>

                        <!-- Trust & Heritage Metrics -->
                        <div class="hero-metrics">
                            <div class="metric-item">
                                <span class="metric-value">Foto &amp; Video 5s</span>
                                <span class="metric-label">Hasil Virtual Try-On</span>
                            </div>
                            <div class="metric-divider" aria-hidden="true"></div>
                            <div class="metric-item">
                                <span class="metric-value">150+ Motif</span>
                                <span class="metric-label">Batik Tulis &amp; Cap Asli</span>
                            </div>
                            <div class="metric-divider" aria-hidden="true"></div>
                            <div class="metric-item">
                                <span class="metric-value">4.9 / 5.0</span>
                                <span class="metric-label">Ulasan Pelanggan</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Mobile App Preview -->
                    <div class="hero-visual">
                        <div class="phone-frame">
                            <div class="phone-notch" aria-hidden="true">
                                <span class="notch-speaker"></span>
                            </div>

                            <div class="phone-screen">
                                <!-- App Status Bar -->
                                <div class="app-topbar">
                                    <strong>ARBatik App</strong>
                                    <span class="ai-mode-badge">
                                        <template v-if="heroPreviewMode === 'video'">
                                            <Video :size="12" />
                                            <span>Video 5 Detik</span>
                                        </template>
                                        <template v-else>
                                            <Camera :size="12" />
                                            <span>Hasil Foto</span>
                                        </template>
                                    </span>
                                </div>

                                <!-- Mini Thumbnails inside Phone: Model + Product -->
                                <div class="phone-input-thumbs">
                                    <div class="mini-thumb-item">
                                        <img
                                            :src="selectedExample.modelPhoto"
                                            :alt="selectedExample.modelName"
                                        />
                                        <span>Foto Model</span>
                                    </div>
                                    <span class="plus-sign">+</span>
                                    <div class="mini-thumb-item">
                                        <img
                                            :src="selectedExample.productPhoto"
                                            :alt="selectedExample.productName"
                                        />
                                        <span>Foto Produk</span>
                                    </div>
                                </div>

                                <!-- Result Preview inside Phone -->
                                <div class="ai-result-stage">
                                    <div class="stage-format-toggle">
                                        <button
                                            type="button"
                                            :class="{ active: heroPreviewMode === 'photo' }"
                                            @click="heroPreviewMode = 'photo'"
                                        >
                                            Foto
                                        </button>
                                        <button
                                            type="button"
                                            :class="{ active: heroPreviewMode === 'video' }"
                                            @click="heroPreviewMode = 'video'"
                                        >
                                            Video 5s
                                        </button>
                                    </div>

                                    <video
                                        v-if="
                                            heroPreviewMode === 'video' &&
                                            selectedExample.resultVideoUrl
                                        "
                                        class="phone-result-media"
                                        :src="selectedExample.resultVideoUrl"
                                        :poster="selectedExample.resultVideoPoster"
                                        autoplay
                                        muted
                                        loop
                                        playsinline
                                    ></video>
                                    <img
                                        v-else
                                        class="phone-result-media"
                                        :src="
                                            heroPreviewMode === 'video'
                                                ? selectedExample.resultVideoPoster ||
                                                  selectedExample.resultPhoto
                                                : selectedExample.resultPhoto
                                        "
                                        :alt="`Hasil Try-On ${selectedExample.productName}`"
                                    />
                                </div>

                                <!-- Bottom App Card inside Phone -->
                                <div class="phone-bottom-sheet">
                                    <div class="sheet-product-info">
                                        <div>
                                            <span class="sheet-motif-tag">
                                                {{ selectedExample.productOrigin }}
                                            </span>
                                            <h4>{{ selectedExample.productName }}</h4>
                                        </div>
                                        <span class="sheet-price">
                                            {{ selectedExample.productPrice }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- CONTOH HASIL VIRTUAL TRY-ON SECTION -->
            <section id="virtual-try-on" class="section-tryon">
                <div class="container">
                    <div class="section-header">
                        <span class="section-eyebrow">CONTOH HASIL VIRTUAL TRY-ON</span>
                        <h2 class="section-title">
                            Bagaimana Hasil Foto &amp; Video 5 Detik di Aplikasi Arbatik?
                        </h2>
                        <p class="section-desc">
                            Berikut adalah contoh hasil penggabungan
                            <strong>Foto Model / User</strong> dan
                            <strong>Foto Produk Batik</strong> di aplikasi Arbatik yang menghasilkan
                            tampilan <strong>Foto</strong> serta <strong>Video 5 Detik</strong>.
                        </p>
                    </div>

                    <!-- Example Case Tabs -->
                    <div class="example-tabs" role="tablist" aria-label="Pilih Contoh Virtual Try-On">
                        <button
                            v-for="item in tryOns"
                            :key="item.id"
                            type="button"
                            role="tab"
                            :aria-selected="selectedExample.id === item.id"
                            class="example-tab-btn"
                            :class="{ active: selectedExample.id === item.id }"
                            @click="selectedExampleId = item.id"
                        >
                            {{ item.label }}
                        </button>
                    </div>

                    <!-- 4-Column Showcase Grid -->
                    <div class="example-showcase-grid">
                        <!-- Card 1: Foto Model / User -->
                        <div
                            class="showcase-card clickable"
                            role="button"
                            tabindex="0"
                            aria-label="Perbesar foto model"
                            @click="
                                openLightbox({
                                    type: 'image',
                                    src: selectedExample.modelPhoto,
                                    title: selectedExample.modelName,
                                })
                            "
                            @keydown.enter.prevent="
                                openLightbox({
                                    type: 'image',
                                    src: selectedExample.modelPhoto,
                                    title: selectedExample.modelName,
                                })
                            "
                            @keydown.space.prevent="
                                openLightbox({
                                    type: 'image',
                                    src: selectedExample.modelPhoto,
                                    title: selectedExample.modelName,
                                })
                            "
                        >
                            <div class="showcase-step-header">
                                <span class="step-badge">1. FOTO MODEL / USER</span>
                            </div>
                            <div class="showcase-media-wrap">
                                <img
                                    :src="selectedExample.modelPhoto"
                                    :alt="selectedExample.modelName"
                                    loading="lazy"
                                />
                            </div>
                            <div class="showcase-caption">
                                <strong>{{ selectedExample.modelName }}</strong>
                                <span v-if="selectedExample.modelStats">
                                    {{ selectedExample.modelStats }}
                                </span>
                            </div>
                        </div>

                        <!-- Card 2: Foto Produk Batik -->
                        <div
                            class="showcase-card clickable"
                            role="button"
                            tabindex="0"
                            aria-label="Perbesar foto produk"
                            @click="
                                openLightbox({
                                    type: 'image',
                                    src: selectedExample.productPhoto,
                                    title: selectedExample.productName,
                                })
                            "
                            @keydown.enter.prevent="
                                openLightbox({
                                    type: 'image',
                                    src: selectedExample.productPhoto,
                                    title: selectedExample.productName,
                                })
                            "
                            @keydown.space.prevent="
                                openLightbox({
                                    type: 'image',
                                    src: selectedExample.productPhoto,
                                    title: selectedExample.productName,
                                })
                            "
                        >
                            <div class="showcase-step-header">
                                <span class="step-badge">2. FOTO PRODUK BATIK</span>
                            </div>
                            <div class="showcase-media-wrap">
                                <img
                                    :src="selectedExample.productPhoto"
                                    :alt="selectedExample.productName"
                                    loading="lazy"
                                />
                            </div>
                            <div class="showcase-caption">
                                <strong>{{ selectedExample.productName }}</strong>
                                <span>
                                    {{
                                        [
                                            selectedExample.productOrigin,
                                            selectedExample.productPrice,
                                        ]
                                            .filter(Boolean)
                                            .join(' • ') || 'Koleksi Batik Arbatik'
                                    }}
                                </span>
                            </div>
                        </div>

                        <!-- Card 3: Hasil Foto Try-On -->
                        <div
                            class="showcase-card result-highlight clickable"
                            role="button"
                            tabindex="0"
                            aria-label="Perbesar hasil foto try-on"
                            @click="
                                openLightbox({
                                    type: 'image',
                                    src: selectedExample.resultPhoto,
                                    title: `Hasil Foto — ${selectedExample.productName}`,
                                })
                            "
                            @keydown.enter.prevent="
                                openLightbox({
                                    type: 'image',
                                    src: selectedExample.resultPhoto,
                                    title: `Hasil Foto — ${selectedExample.productName}`,
                                })
                            "
                            @keydown.space.prevent="
                                openLightbox({
                                    type: 'image',
                                    src: selectedExample.resultPhoto,
                                    title: `Hasil Foto — ${selectedExample.productName}`,
                                })
                            "
                        >
                            <div class="showcase-step-header">
                                <span class="step-badge primary">
                                    <Camera :size="13" />
                                    <span>3. HASIL FOTO TRY-ON</span>
                                </span>
                            </div>
                            <div class="showcase-media-wrap">
                                <img
                                    :src="selectedExample.resultPhoto"
                                    :alt="`Hasil Foto ${selectedExample.productName}`"
                                    loading="lazy"
                                />
                            </div>
                            <div class="showcase-caption">
                                <strong>Hasil Foto Saat Dikenakan</strong>
                                <span>Tampilan baju batik menyatu mengikuti postur model</span>
                            </div>
                        </div>

                        <!-- Card 4: Hasil Video 5 Detik -->
                        <div
                            class="showcase-card result-highlight clickable"
                            role="button"
                            tabindex="0"
                            aria-label="Perbesar hasil video try-on"
                            @click="openVideoCardLightbox"
                            @keydown.enter.prevent="openVideoCardLightbox"
                            @keydown.space.prevent="openVideoCardLightbox"
                        >
                            <div class="showcase-step-header">
                                <span class="step-badge primary">
                                    <Video :size="13" />
                                    <span>4. HASIL VIDEO 5 DETIK</span>
                                </span>
                            </div>
                            <div class="showcase-media-wrap video-wrap">
                                <video
                                    v-if="selectedExample.resultVideoUrl"
                                    :src="selectedExample.resultVideoUrl"
                                    :poster="selectedExample.resultVideoPoster"
                                    controls
                                    muted
                                    loop
                                    playsinline
                                ></video>
                                <img
                                    v-else
                                    :src="
                                        selectedExample.resultVideoPoster ||
                                        selectedExample.resultPhoto
                                    "
                                    :alt="`Hasil Video ${selectedExample.productName}`"
                                    loading="lazy"
                                />
                                <span class="duration-chip">
                                    {{ selectedExample.videoDuration }}
                                </span>
                            </div>
                            <div class="showcase-caption">
                                <strong>Hasil Video Singkat 5 Detik</strong>
                                <span>Melihat jatuhnya kain batik saat bergerak</span>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Summary Bar under Example Showcase -->
                    <div class="example-summary-bar">
                        <p>{{ selectedExample.caption }}</p>
                        <button
                            type="button"
                            class="btn-solid-primary"
                            @click="handleDownloadClick('android')"
                        >
                            <Download :size="17" />
                            <span>Coba Pakai Foto Kamu di Aplikasi</span>
                        </button>
                    </div>
                </div>
            </section>

            <!-- KEY FEATURES SECTION -->
            <section id="fitur" class="section-features">
                <div class="container">
                    <div class="section-header">
                        <span class="section-eyebrow">KEUNGGULAN TOKO ARBATIK</span>
                        <h2 class="section-title">Belanja Batik Online Lebih Pasti &amp; Nyaman</h2>
                        <p class="section-desc">
                            Koleksi batik tulis dan cap pilihan dengan jahitan rapi, dilengkapi
                            kemudahan mencoba baju secara virtual langsung dari HP Anda.
                        </p>
                    </div>

                    <div class="features-grid">
                        <article class="feature-card">
                            <div class="feature-icon-box">
                                <ImageIcon :size="24" />
                            </div>
                            <h3>Upload Foto Sendiri atau Model</h3>
                            <p>
                                Unggah foto Anda dari galeri HP atau pilih foto model yang tersedia
                                untuk melihat kecocokan motif batik di badan.
                            </p>
                        </article>

                        <article class="feature-card">
                            <div class="feature-icon-box">
                                <Video :size="24" />
                            </div>
                            <h3>Hasil Foto &amp; Video 5 Detik</h3>
                            <p>
                                Lihat hasil penggabungan foto Anda dan produk batik pilihan dalam
                                bentuk foto maupun video singkat berdurasi 5 detik.
                            </p>
                        </article>

                        <article class="feature-card">
                            <div class="feature-icon-box">
                                <ShieldCheck :size="24" />
                            </div>
                            <h3>Batik Asli Pengrajin Lokal</h3>
                            <p>
                                Setiap produk dilengkapi keterangan jenis batik (Batik Tulis, Cap,
                                atau Kombinasi) serta daerah asalnya secara jujur.
                            </p>
                        </article>

                        <article class="feature-card">
                            <div class="feature-icon-box">
                                <MessageCircle :size="24" />
                            </div>
                            <h3>Transfer Bank, Upload Bukti &amp; Chat</h3>
                            <p>
                                Pembayaran mudah via transfer bank disertai fitur upload bukti bayar
                                dan fitur chat langsung untuk konsultasi ukuran maupun pesanan.
                            </p>
                        </article>
                    </div>
                </div>
            </section>

            <!-- FEATURED BATIK CATALOG SECTION -->
            <section id="katalog" class="section-catalog">
                <div class="container">
                    <div class="catalog-top-row">
                        <div>
                            <span class="section-eyebrow">KOLEKSI PILIHAN</span>
                            <h2 class="section-title">Katalog Busana Batik Terpopuler</h2>
                        </div>

                        <!-- Category Filter Buttons -->
                        <div
                            class="category-tabs"
                            role="tablist"
                            aria-label="Filter Kategori Batik"
                        >
                            <button
                                v-for="cat in categoryOptions"
                                :key="cat"
                                type="button"
                                role="tab"
                                :aria-selected="activeCategory === cat"
                                class="category-tab"
                                :class="{ active: activeCategory === cat }"
                                @click="activeCategory = cat"
                            >
                                {{ cat }}
                            </button>
                        </div>
                    </div>

                    <div class="catalog-grid">
                        <article
                            v-for="product in filteredProducts"
                            :key="product.id"
                            class="product-card"
                        >
                            <div class="product-visual">
                                <img
                                    :src="product.imageUrl"
                                    :alt="product.name"
                                    class="product-img"
                                    loading="lazy"
                                />
                                <div class="product-badges">
                                    <span v-if="product.badge" class="badge-heritage">
                                        {{ product.badge }}
                                    </span>
                                    <span v-if="product.tryOnReady" class="badge-tryon">
                                        <Sparkles :size="11" />
                                        <span>Bisa Try-On</span>
                                    </span>
                                </div>
                                <span
                                    v-if="product.origin && product.origin !== '-'"
                                    class="product-origin-pill"
                                >
                                    Asal: {{ product.origin }}
                                </span>
                            </div>

                            <div class="product-body">
                                <div class="product-meta">
                                    <span class="product-cat">{{ product.category }}</span>
                                    <span
                                        v-if="product.rating || product.sold"
                                        class="product-rating"
                                    >
                                        <Star :size="13" fill="currentColor" />
                                        <span>
                                            {{ product.rating
                                            }}{{ product.rating && product.sold ? ' ' : ''
                                            }}{{ product.sold ? `(${product.sold})` : '' }}
                                        </span>
                                    </span>
                                </div>
                                <h3 class="product-title">{{ product.name }}</h3>
                                <div class="product-price-row">
                                    <div>
                                        <span class="price-current">{{ product.price }}</span>
                                        <span v-if="product.originalPrice" class="price-strike">
                                            {{ product.originalPrice }}
                                        </span>
                                    </div>
                                    <button
                                        type="button"
                                        class="btn-card-action"
                                        @click="handleDownloadClick('android')"
                                    >
                                        Coba di App
                                    </button>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <!-- HOW IT WORKS SECTION FOR USERS -->
            <section id="cara-kerja" class="section-steps">
                <div class="container">
                    <div class="section-header">
                        <span class="section-eyebrow">CARA BELANJA</span>
                        <h2 class="section-title">3 Langkah Mudah Belanja di Aplikasi Arbatik</h2>
                        <p class="section-desc">
                            Coba dulu tampilan baju batik pilihan Anda menggunakan foto dari HP
                            sebelum memesan.
                        </p>
                    </div>

                    <div class="steps-grid">
                        <div class="step-card">
                            <div class="step-number">01</div>
                            <h3>Upload Foto &amp; Pilih Batik</h3>
                            <p>
                                Unggah foto diri Anda dari galeri HP (atau pilih foto model), lalu
                                pilih kemeja, blouse, atau tunik batik dari katalog Arbatik.
                            </p>
                        </div>

                        <div class="step-card">
                            <div class="step-number">02</div>
                            <h3>Lihat Hasil Foto &amp; Video 5 Detik</h3>
                            <p>
                                Fitur Virtual Try-On akan menampilkan Anda mengenakan baju batik
                                tersebut dalam bentuk foto maupun video singkat 5 detik.
                            </p>
                        </div>

                        <div class="step-card">
                            <div class="step-number">03</div>
                            <h3>Transfer, Upload Bukti &amp; Chat</h3>
                            <p>
                                Pilih ukuran batik Anda, lakukan pembayaran via transfer bank, lalu
                                upload bukti pembayaran di aplikasi. Tersedia juga fitur chat untuk
                                berkonsultasi langsung.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- DOWNLOAD CTA BANNER SECTION -->
            <section id="download" class="section-download-cta">
                <div class="container">
                    <div class="download-banner">
                        <div class="download-banner-content">
                            <span class="download-badge">APLIKASI MOBILE ARBATIK</span>
                            <h2>Unduh Aplikasi Arbatik &amp; Coba Baju Batik dari HP Anda</h2>
                            <p>
                                Dapatkan kemudahan memilih koleksi batik Nusantara, fitur coba baju
                                virtual dengan foto sendiri, serta layanan chat dan pemesanan
                                langsung di genggaman Anda.
                            </p>

                            <div class="download-buttons-row">
                                <button
                                    type="button"
                                    class="btn-download-banner primary"
                                    :disabled="isCheckingDownload"
                                    @click="handleDownloadClick('android')"
                                >
                                    <Download :size="20" />
                                    <div class="btn-text-wrap">
                                        <span class="btn-small-label">
                                            UNDUH GRATIS (APK{{
                                                latestApps.android?.version
                                                    ? ` • v${latestApps.android.version}`
                                                    : ''
                                            }}{{
                                                latestApps.android?.file_size
                                                    ? ` • ${formatBytes(latestApps.android.file_size)}`
                                                    : ''
                                            }})
                                        </span>
                                        <span class="btn-main-label">Download untuk Android</span>
                                    </div>
                                </button>

                                <button
                                    type="button"
                                    class="btn-download-banner outline"
                                    :disabled="isCheckingDownload"
                                    @click="handleDownloadClick('ios')"
                                >
                                    <Smartphone :size="20" />
                                    <div class="btn-text-wrap">
                                        <span class="btn-small-label">
                                            {{
                                                latestApps.ios?.download_url ||
                                                latestApps.ios?.file_url
                                                    ? `TERSEDIA (v${latestApps.ios.version})`
                                                    : 'SEGERA HADIR'
                                            }}
                                        </span>
                                        <span class="btn-main-label">Versi iOS / App Store</span>
                                    </div>
                                </button>
                            </div>

                            <div class="download-specs">
                                <span v-if="latestApps.android?.version" class="spec-item">
                                    <Check :size="15" />
                                    <span>Versi Terbaru v{{ latestApps.android.version }}</span>
                                </span>
                                <span class="spec-item">
                                    <Check :size="15" />
                                    <span>Android 8.0 ke atas</span>
                                </span>
                                <span class="spec-item">
                                    <Check :size="15" />
                                    <span>Gratis Coba Virtual Try-On</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- FAQ SECTION -->
            <section id="faq" class="section-faq">
                <div class="container faq-container">
                    <div class="section-header">
                        <span class="section-eyebrow">PERTANYAAN UMUM</span>
                        <h2 class="section-title">Hal yang Sering Ditanyakan</h2>
                        <p class="section-desc">
                            Informasi seputar fitur Virtual Try-On, keaslian produk batik, dan cara
                            pembayaran.
                        </p>
                    </div>

                    <div class="faq-list">
                        <div
                            v-for="(faq, index) in faqs"
                            :key="faq.question"
                            class="faq-item"
                            :class="{ open: openFaqIndex === index }"
                        >
                            <button
                                type="button"
                                class="faq-question"
                                :aria-expanded="openFaqIndex === index"
                                @click="openFaqIndex = openFaqIndex === index ? null : index"
                            >
                                <span>{{ faq.question }}</span>
                                <span class="faq-chevron" aria-hidden="true">
                                    <ChevronUp v-if="openFaqIndex === index" :size="18" />
                                    <ChevronDown v-else :size="18" />
                                </span>
                            </button>
                            <div v-if="openFaqIndex === index" class="faq-answer">
                                <p>{{ faq.answer }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- FOOTER -->
        <footer class="site-footer">
            <div class="container footer-grid">
                <div class="footer-brand">
                    <div class="brand-logo footer-logo-badge">
                        <img :src="logoUrl" alt="Arbatik" class="brand-logo-img footer-img" />
                    </div>
                    <p class="footer-brand-name">
                        {{ settings?.name || 'Arbatik Heritage Indonesia' }}
                    </p>
                    <p class="footer-desc">
                        {{
                            settings?.description ||
                            'Toko online busana batik autentik Indonesia. Coba kecocokan baju batik dengan foto Anda sendiri — hasil foto maupun video 5 detik.'
                        }}
                    </p>
                    <div class="footer-social">
                        <a
                            :href="waUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="social-btn"
                            aria-label="WhatsApp"
                        >
                            <MessageCircle :size="18" />
                        </a>
                        <a
                            v-if="settings?.social_media?.instagram_url"
                            :href="settings.social_media.instagram_url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="social-btn"
                            aria-label="Instagram"
                        >
                            <AtSign :size="18" />
                        </a>
                        <a
                            v-if="settings?.social_media?.facebook_url"
                            :href="settings.social_media.facebook_url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="social-btn"
                            aria-label="Facebook"
                        >
                            <Globe :size="18" />
                        </a>
                    </div>
                </div>

                <div class="footer-col">
                    <h4>Katalog Batik</h4>
                    <ul>
                        <li v-for="cat in categories" :key="cat.id">
                            <a href="#katalog">{{ cat.name }}</a>
                        </li>
                        <li v-if="categories.length === 0">
                            <a href="#katalog">Semua Koleksi</a>
                        </li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Menu</h4>
                    <ul>
                        <li><a href="#beranda">Beranda</a></li>
                        <li><a href="#virtual-try-on">Contoh Try-On</a></li>
                        <li><a href="#fitur">Keunggulan</a></li>
                        <li><a href="#cara-kerja">Cara Belanja</a></li>
                        <li><a href="#faq">Bantuan &amp; FAQ</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Bantuan</h4>
                    <ul>
                        <li>
                            <a :href="waUrl" target="_blank" rel="noopener noreferrer">
                                Chat WhatsApp
                            </a>
                        </li>
                        <li v-if="settings?.contact?.email">
                            <a :href="`mailto:${settings.contact.email}`">
                                {{ settings.contact.email }}
                            </a>
                        </li>
                        <li v-if="settings?.contact?.phone">
                            <a :href="`tel:${settings.contact.phone}`">
                                {{ settings.contact.phone }}
                            </a>
                        </li>
                        <li v-if="settings?.address">
                            <span>{{ settings.address }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="container footer-bottom">
                <p>
                    &copy; {{ new Date().getFullYear() }}
                    {{ settings?.name || 'Arbatik Heritage Indonesia' }}. Hak Cipta Dilindungi.
                </p>
                <p>Warisan Batik Nusantara</p>
            </div>
        </footer>

        <!-- FLOATING WHATSAPP BUTTON -->
        <a
            :href="waUrl"
            target="_blank"
            rel="noopener noreferrer"
            class="floating-wa-btn"
            aria-label="Chat WhatsApp Toko Arbatik"
            title="Hubungi Kami via WhatsApp"
        >
            <MessageCircle :size="20" />
            <span class="wa-label">Chat WhatsApp</span>
        </a>

        <!-- CUSTOMER DOWNLOAD MODAL -->
        <div
            v-if="downloadModalOpen"
            class="modal-backdrop"
            role="dialog"
            aria-modal="true"
            aria-labelledby="download-modal-title"
        >
            <div class="modal-card">
                <div class="modal-header">
                    <span class="modal-badge">UNDUH APLIKASI ARBATIK</span>
                    <button
                        type="button"
                        class="modal-close"
                        aria-label="Tutup dialog"
                        @click="downloadModalOpen = false"
                    >
                        <X :size="16" />
                    </button>
                </div>

                <h3 id="download-modal-title">
                    <template v-if="modalCustomMessage">
                        Pemeliharaan Aplikasi ({{
                            selectedPlatform === 'android' ? 'Android' : 'iOS'
                        }})
                    </template>
                    <template v-else>
                        {{
                            selectedPlatform === 'android'
                                ? 'Unduhan Aplikasi Android Segera Tersedia'
                                : 'Aplikasi Arbatik untuk iOS Segera Hadir'
                        }}
                    </template>
                </h3>

                <p class="modal-text">
                    <template v-if="modalCustomMessage">
                        {{ modalCustomMessage }}
                    </template>
                    <template v-else>
                        Terima kasih atas minat Anda berbelanja di <strong>Arbatik</strong>. File
                        unduhan aplikasi sedang dalam tahap persiapan akhir dan akan segera dapat
                        diunduh langsung dari halaman ini.
                    </template>
                </p>

                <div class="modal-actions">
                    <button
                        type="button"
                        class="btn-modal-close"
                        @click="downloadModalOpen = false"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>

        <!-- TRY-ON LIGHTBOX -->
        <div
            v-if="lightbox"
            class="lightbox-backdrop"
            role="dialog"
            aria-modal="true"
            :aria-label="lightbox.title"
            tabindex="-1"
            @click="closeLightbox"
            @keydown.escape="closeLightbox"
        >
            <div class="lightbox-content" role="presentation" @click.stop>
                <div class="lightbox-header">
                    <span class="lightbox-title">{{ lightbox.title }}</span>
                    <button
                        type="button"
                        class="modal-close"
                        aria-label="Tutup"
                        @click="closeLightbox"
                    >
                        <X :size="18" />
                    </button>
                </div>
                <div class="lightbox-body">
                    <video
                        v-if="lightbox.type === 'video'"
                        :src="lightbox.src"
                        :poster="lightbox.poster"
                        controls
                        autoplay
                        muted
                        loop
                        playsinline
                    ></video>
                    <img v-else :src="lightbox.src" :alt="lightbox.title" />
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.arbatik-landing {
    /* Primary Brand Colors (Batik Sogan Brown) */
    --color-primary: #704f38;
    --color-primary-dark: #4a3423;
    --color-primary-light: #8d674b;

    /* Accent Colors (Batik Sogan Gold & Amber) */
    --color-accent: #d97706;
    --color-accent-light: #fde68a;
    --color-accent-dark: #b45309;

    /* Backgrounds & Surface */
    --color-bg-primary: #704f38;
    --color-bg-light: #f8fafc;
    --color-surface: #ffffff;
    --color-card: #ffffff;
    --color-surface-variant: #f1f5f9;

    /* Text Colors */
    --color-text-primary: #0f172a;
    --color-text-secondary: #64748b;
    --color-text-muted: #94a3b8;
    --color-text-light: #ffffff;

    /* Status & Feedback Colors */
    --color-success: #10b981;
    --color-warning: #f59e0b;
    --color-error: #ef4444;
    --color-info: #3b82f6;

    /* Borders & Dividers */
    --color-border: #e2e8f0;
    --color-border-focused: #d97706;
    --color-divider: #eef2f6;

    /* Typography */
    --font-sans:
        'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    --font-serif: 'Cormorant Garamond', Georgia, serif;

    font-family: var(--font-sans);
    color: var(--color-text-primary);
    background-color: var(--color-surface);
    line-height: 1.6;
    overflow-x: hidden;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

.arbatik-landing *,
.arbatik-landing *::before,
.arbatik-landing *::after {
    box-sizing: border-box;
    border-color: var(--color-border);
}

.arbatik-landing a {
    color: inherit;
    text-decoration: none;
}

.arbatik-landing button {
    font-family: inherit;
    cursor: pointer;
    border: none;
    background: none;
}

.arbatik-landing img {
    display: block;
    max-width: 100%;
}

.container {
    width: 100%;
    max-width: 1200px;
    margin-inline: auto;
    padding-inline: 1.25rem;
}

@media (min-width: 768px) {
    .container {
        padding-inline: 2rem;
    }
}

/* Announcement Bar */
.announcement-bar {
    background-color: var(--color-primary-dark);
    color: var(--color-text-light);
    font-size: 0.84rem;
    padding: 0.5rem 0;
}

.announcement-inner {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.65rem;
    flex-wrap: wrap;
    text-align: center;
}

.announcement-badge {
    background-color: var(--color-primary);
    color: var(--color-accent-light);
    font-weight: 700;
    font-size: 0.68rem;
    padding: 0.12rem 0.5rem;
    border-radius: 0.25rem;
    letter-spacing: 0.05em;
}

.announcement-link {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    color: var(--color-accent-light);
    font-weight: 600;
    text-decoration: underline;
    text-underline-offset: 3px;
}

/* Sticky Header */
.site-header {
    position: sticky;
    top: 0;
    z-index: 50;
    background-color: var(--color-surface);
    border-bottom: 1px solid var(--color-border);
}

.header-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 4.5rem;
    gap: 1rem;
}

.brand-logo {
    display: inline-flex;
    align-items: center;
}

.brand-logo-img {
    height: 3rem;
    width: auto;
    object-fit: contain;
}

.desktop-nav {
    display: none;
    align-items: center;
    gap: 2rem;
    font-size: 0.92rem;
    font-weight: 600;
    color: var(--color-text-secondary);
}

.desktop-nav a {
    transition: color 0.15s ease;
}

.desktop-nav a:hover {
    color: var(--color-primary);
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.btn-primary-cta {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    background-color: var(--color-primary);
    color: var(--color-text-light);
    font-size: 0.86rem;
    font-weight: 600;
    padding: 0.6rem 1.1rem;
    border-radius: 0.5rem;
    transition: background-color 0.15s ease;
}

.btn-primary-cta:hover {
    background-color: var(--color-primary-dark);
}

.btn-primary-cta.full-width {
    width: 100%;
    padding: 0.8rem 1.2rem;
}

.mobile-menu-toggle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.4rem;
    height: 2.4rem;
    border-radius: 0.5rem;
    border: 1px solid var(--color-border);
    color: var(--color-text-primary);
}

.mobile-drawer {
    background-color: var(--color-surface);
    border-bottom: 1px solid var(--color-border);
    padding: 1rem 1.25rem 1.5rem;
}

.mobile-drawer-nav {
    display: flex;
    flex-direction: column;
    margin-bottom: 1rem;
}

.mobile-drawer-nav a {
    padding: 0.75rem 0.25rem;
    font-weight: 600;
    color: var(--color-text-primary);
    border-bottom: 1px solid var(--color-divider);
}

@media (min-width: 960px) {
    .desktop-nav {
        display: flex;
    }

    .mobile-menu-toggle {
        display: none;
    }
}

/* Hero Section */
.hero-section {
    background-color: var(--color-bg-light);
    border-bottom: 1px solid var(--color-border);
    padding: 4rem 0 5rem;
}

.hero-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 3rem;
    align-items: center;
}

@media (min-width: 992px) {
    .hero-section {
        padding: 5rem 0 6rem;
    }

    .hero-grid {
        grid-template-columns: 1.15fr 0.85fr;
        gap: 3.5rem;
    }
}

.hero-pill {
    display: inline-block;
    background-color: var(--color-surface);
    border: 1px solid var(--color-border);
    color: var(--color-primary);
    padding: 0.35rem 0.85rem;
    border-radius: 0.375rem;
    font-size: 0.8rem;
    font-weight: 700;
    margin-bottom: 1.25rem;
}

.hero-title {
    font-size: clamp(2rem, 4vw, 3.2rem);
    font-weight: 800;
    color: var(--color-text-primary);
    line-height: 1.18;
    letter-spacing: -0.02em;
    margin-bottom: 1.15rem;
}

.text-serif-italic {
    font-family: var(--font-serif);
    font-style: italic;
    font-weight: 700;
    color: var(--color-primary);
    font-size: 1.06em;
}

.hero-subtitle {
    font-size: 1.05rem;
    color: var(--color-text-secondary);
    max-width: 38rem;
    margin-bottom: 2rem;
}

.hero-cta-group {
    display: flex;
    flex-wrap: wrap;
    gap: 0.85rem;
    margin-bottom: 2.5rem;
}

.btn-solid-primary {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.85rem 1.45rem;
    border-radius: 0.5rem;
    background-color: var(--color-primary);
    color: var(--color-text-light);
    font-weight: 700;
    font-size: 0.92rem;
    transition: background-color 0.15s ease;
}

.btn-solid-primary:hover {
    background-color: var(--color-primary-dark);
}

.btn-solid-outline {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0.85rem 1.35rem;
    border-radius: 0.5rem;
    background-color: var(--color-surface);
    border: 1px solid var(--color-border);
    color: var(--color-text-primary);
    font-weight: 600;
    font-size: 0.92rem;
    transition: border-color 0.15s ease;
}

.btn-solid-outline:hover {
    border-color: var(--color-primary);
    color: var(--color-primary);
}

.hero-metrics {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--color-border);
}

.metric-item {
    display: flex;
    flex-direction: column;
}

.metric-value {
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--color-primary-dark);
}

.metric-label {
    font-size: 0.8rem;
    color: var(--color-text-secondary);
}

.metric-divider {
    width: 1px;
    height: 2rem;
    background-color: var(--color-border);
}

/* Clean Phone Mockup in Hero */
.hero-visual {
    display: flex;
    justify-content: center;
    align-items: center;
}

.phone-frame {
    width: 100%;
    max-width: 320px;
    background-color: var(--color-primary-dark);
    border-radius: 2rem;
    padding: 0.65rem;
    border: 1px solid var(--color-border);
    box-shadow: 0 16px 36px rgba(15, 23, 42, 0.1);
}

.phone-notch {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 0.9rem;
    margin-bottom: 0.35rem;
}

.notch-speaker {
    width: 2.5rem;
    height: 0.25rem;
    border-radius: 999px;
    background-color: rgba(255, 255, 255, 0.2);
}

.phone-screen {
    background-color: var(--color-surface);
    color: var(--color-text-primary);
    border-radius: 1.45rem;
    overflow: hidden;
}

.app-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.65rem 0.9rem;
    background-color: var(--color-primary);
    color: var(--color-text-light);
    font-size: 0.74rem;
}

.ai-mode-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.28rem;
    background-color: var(--color-primary-dark);
    color: var(--color-text-light);
    padding: 0.16rem 0.5rem;
    border-radius: 0.3rem;
    font-size: 0.64rem;
    font-weight: 600;
}

.phone-input-thumbs {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.65rem;
    padding: 0.55rem;
    background-color: var(--color-surface-variant);
    border-bottom: 1px solid var(--color-border);
}

.mini-thumb-item {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    background-color: var(--color-surface);
    padding: 0.25rem 0.5rem;
    border-radius: 0.4rem;
    border: 1px solid var(--color-border);
    font-size: 0.68rem;
    font-weight: 600;
    color: var(--color-text-primary);
}

.mini-thumb-item img {
    width: 1.5rem;
    height: 1.5rem;
    border-radius: 0.25rem;
    object-fit: cover;
}

.plus-sign {
    color: var(--color-primary);
    font-weight: 800;
    font-size: 0.85rem;
}

.ai-result-stage {
    position: relative;
    background-color: var(--color-bg-light);
    height: 250px;
    overflow: hidden;
    border-bottom: 1px solid var(--color-border);
}

.phone-result-media {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.stage-format-toggle {
    position: absolute;
    top: 10px;
    right: 10px;
    z-index: 2;
    display: flex;
    background-color: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: 0.4rem;
    padding: 0.15rem;
}

.stage-format-toggle button {
    color: var(--color-text-secondary);
    font-size: 0.66rem;
    font-weight: 700;
    padding: 0.2rem 0.55rem;
    border-radius: 0.28rem;
}

.stage-format-toggle button.active {
    background-color: var(--color-primary);
    color: var(--color-text-light);
}

.phone-bottom-sheet {
    padding: 0.85rem 0.95rem;
    background-color: var(--color-surface);
}

.sheet-product-info {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.5rem;
}

.sheet-motif-tag {
    font-size: 0.66rem;
    font-weight: 700;
    color: var(--color-text-secondary);
    text-transform: uppercase;
}

.sheet-product-info h4 {
    font-size: 0.84rem;
    font-weight: 700;
    color: var(--color-text-primary);
}

.sheet-price {
    font-weight: 800;
    font-size: 0.84rem;
    color: var(--color-primary);
    white-space: nowrap;
}

/* Section Headers */
.section-header {
    text-align: center;
    max-width: 44rem;
    margin: 0 auto 2.25rem;
}

.section-eyebrow {
    display: inline-block;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    color: var(--color-primary);
    margin-bottom: 0.5rem;
}

.section-title {
    font-size: clamp(1.65rem, 2.8vw, 2.25rem);
    font-weight: 800;
    color: var(--color-text-primary);
    line-height: 1.22;
    margin-bottom: 0.75rem;
}

.section-desc {
    color: var(--color-text-secondary);
    font-size: 0.98rem;
}

/* Contoh Hasil Virtual Try-On */
.section-tryon {
    padding: 5rem 0;
    background-color: var(--color-surface);
}

.example-tabs {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 0.6rem;
    margin-bottom: 2rem;
}

.example-tab-btn {
    padding: 0.6rem 1.2rem;
    border-radius: 0.45rem;
    border: 1px solid var(--color-border);
    background-color: var(--color-bg-light);
    color: var(--color-text-secondary);
    font-size: 0.88rem;
    font-weight: 600;
    transition: all 0.15s ease;
}

.example-tab-btn:hover {
    border-color: var(--color-primary);
    color: var(--color-primary);
}

.example-tab-btn.active {
    background-color: var(--color-primary);
    color: var(--color-text-light);
    border-color: var(--color-primary);
}

.example-showcase-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.25rem;
    margin-bottom: 1.5rem;
}

@media (min-width: 640px) {
    .example-showcase-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 1024px) {
    .example-showcase-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

.showcase-card {
    background-color: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: 0.75rem;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.showcase-card.result-highlight {
    border-color: var(--color-primary);
}

.showcase-card.clickable {
    cursor: zoom-in;
    transition:
        border-color 0.15s ease,
        box-shadow 0.15s ease;
}

.showcase-card.clickable:hover {
    border-color: var(--color-primary);
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.1);
}

.showcase-card.clickable:focus-visible {
    outline: 2px solid var(--color-primary);
    outline-offset: 2px;
}

.showcase-step-header {
    padding: 0.65rem 0.9rem;
    background-color: var(--color-bg-light);
    border-bottom: 1px solid var(--color-border);
}

.step-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    color: var(--color-text-secondary);
}

.step-badge.primary {
    color: var(--color-primary);
}

.showcase-media-wrap {
    position: relative;
    height: 290px;
    background-color: var(--color-surface-variant);
    overflow: hidden;
}

.showcase-media-wrap img,
.showcase-media-wrap video {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.duration-chip {
    position: absolute;
    top: 0.65rem;
    right: 0.65rem;
    background-color: rgba(15, 23, 42, 0.75);
    color: var(--color-text-light);
    font-size: 0.7rem;
    font-weight: 700;
    padding: 0.18rem 0.5rem;
    border-radius: 0.3rem;
    pointer-events: none;
}

.showcase-caption {
    padding: 0.95rem 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
}

.showcase-caption strong {
    font-size: 0.9rem;
    color: var(--color-text-primary);
}

.showcase-caption span {
    font-size: 0.78rem;
    color: var(--color-text-secondary);
}

.example-summary-bar {
    background-color: var(--color-bg-light);
    border: 1px solid var(--color-border);
    border-radius: 0.75rem;
    padding: 1.15rem 1.35rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
    align-items: flex-start;
    justify-content: space-between;
}

@media (min-width: 768px) {
    .example-summary-bar {
        flex-direction: row;
        align-items: center;
    }
}

.example-summary-bar p {
    font-size: 0.9rem;
    color: var(--color-text-secondary);
    max-width: 44rem;
}

/* Features Section */
.section-features {
    padding: 5rem 0;
    background-color: var(--color-bg-light);
    border-top: 1px solid var(--color-border);
    border-bottom: 1px solid var(--color-border);
}

.features-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.25rem;
}

@media (min-width: 640px) {
    .features-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 1024px) {
    .features-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

.feature-card {
    background-color: var(--color-card);
    border: 1px solid var(--color-border);
    border-radius: 0.75rem;
    padding: 1.5rem;
}

.feature-icon-box {
    width: 2.8rem;
    height: 2.8rem;
    border-radius: 0.5rem;
    background-color: var(--color-surface-variant);
    color: var(--color-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
}

.feature-card h3 {
    font-size: 1.02rem;
    font-weight: 700;
    color: var(--color-text-primary);
    margin-bottom: 0.45rem;
}

.feature-card p {
    font-size: 0.88rem;
    color: var(--color-text-secondary);
}

/* Catalog Section */
.section-catalog {
    padding: 5rem 0;
    background-color: var(--color-surface);
}

.catalog-top-row {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    margin-bottom: 2.25rem;
}

@media (min-width: 768px) {
    .catalog-top-row {
        flex-direction: row;
        align-items: flex-end;
        justify-content: space-between;
    }
}

.category-tabs {
    display: flex;
    gap: 0.45rem;
    flex-wrap: wrap;
}

.category-tab {
    padding: 0.5rem 1rem;
    border-radius: 0.4rem;
    border: 1px solid var(--color-border);
    background-color: var(--color-surface);
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--color-text-secondary);
}

.category-tab.active {
    background-color: var(--color-primary);
    color: var(--color-text-light);
    border-color: var(--color-primary);
}

.catalog-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
}

@media (min-width: 640px) {
    .catalog-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 1024px) {
    .catalog-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

.product-card {
    background-color: var(--color-card);
    border: 1px solid var(--color-border);
    border-radius: 0.75rem;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.product-visual {
    position: relative;
    height: 230px;
    background-color: var(--color-surface-variant);
    overflow: hidden;
}

.product-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-badges {
    position: absolute;
    top: 0.75rem;
    left: 0.75rem;
    right: 0.75rem;
    display: flex;
    justify-content: space-between;
    gap: 0.5rem;
}

.badge-heritage {
    background-color: rgba(15, 23, 42, 0.75);
    color: var(--color-text-light);
    font-size: 0.68rem;
    font-weight: 600;
    padding: 0.2rem 0.55rem;
    border-radius: 0.3rem;
}

.badge-tryon {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    background-color: var(--color-surface);
    color: var(--color-primary-dark);
    font-size: 0.68rem;
    font-weight: 700;
    padding: 0.2rem 0.55rem;
    border-radius: 0.3rem;
}

.product-origin-pill {
    position: absolute;
    bottom: 0.75rem;
    left: 0.75rem;
    background-color: rgba(15, 23, 42, 0.7);
    color: var(--color-text-light);
    font-size: 0.7rem;
    padding: 0.18rem 0.5rem;
    border-radius: 0.28rem;
}

.product-body {
    padding: 1.15rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    flex: 1;
}

.product-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.75rem;
    color: var(--color-text-secondary);
}

.product-cat {
    font-weight: 700;
    color: var(--color-primary);
}

.product-rating {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    color: var(--color-accent);
    font-weight: 600;
}

.product-title {
    font-size: 0.98rem;
    font-weight: 700;
    color: var(--color-text-primary);
    line-height: 1.35;
}

.product-price-row {
    margin-top: auto;
    padding-top: 0.75rem;
    border-top: 1px solid var(--color-divider);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
}

.price-current {
    display: block;
    font-size: 1rem;
    font-weight: 800;
    color: var(--color-text-primary);
}

.price-strike {
    font-size: 0.75rem;
    color: var(--color-text-muted);
    text-decoration: line-through;
}

.btn-card-action {
    background-color: var(--color-surface-variant);
    color: var(--color-text-primary);
    font-size: 0.78rem;
    font-weight: 600;
    padding: 0.45rem 0.85rem;
    border-radius: 0.4rem;
    border: 1px solid var(--color-border);
    transition: all 0.15s ease;
}

.btn-card-action:hover {
    background-color: var(--color-primary);
    color: var(--color-text-light);
    border-color: var(--color-primary);
}

/* How It Works (3 Steps) Section */
.section-steps {
    padding: 5rem 0;
    background-color: var(--color-bg-light);
    border-top: 1px solid var(--color-border);
}

.steps-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.25rem;
}

@media (min-width: 860px) {
    .steps-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

.step-card {
    background-color: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: 0.75rem;
    padding: 1.75rem;
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
}

.step-number {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.45rem;
    background-color: var(--color-primary);
    color: var(--color-text-light);
    font-weight: 700;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 0.25rem;
}

.step-card h3 {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--color-text-primary);
}

.step-card p {
    font-size: 0.9rem;
    color: var(--color-text-secondary);
}

/* Download Banner Section */
.section-download-cta {
    padding: 5rem 0;
    background-color: var(--color-surface);
}

.download-banner {
    background-color: var(--color-primary);
    color: var(--color-text-light);
    border-radius: 1rem;
    padding: 2.75rem 1.75rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

@media (min-width: 960px) {
    .download-banner {
        padding: 3.5rem 3rem;
    }
}

.download-banner-content {
    max-width: 44rem;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.download-badge {
    display: inline-block;
    background-color: var(--color-primary-dark);
    color: var(--color-accent-light);
    padding: 0.25rem 0.7rem;
    border-radius: 0.3rem;
    font-size: 0.72rem;
    font-weight: 700;
    margin-bottom: 0.85rem;
}

.download-banner-content h2 {
    font-size: clamp(1.55rem, 2.8vw, 2.15rem);
    font-weight: 800;
    line-height: 1.22;
    margin-bottom: 0.75rem;
}

.download-banner-content p {
    color: rgba(255, 255, 255, 0.88);
    margin-bottom: 1.75rem;
    max-width: 38rem;
    font-size: 0.95rem;
}

.download-buttons-row {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.85rem;
    margin-bottom: 1.5rem;
}

.btn-download-banner {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1.25rem;
    border-radius: 0.5rem;
    text-align: left;
}

.btn-download-banner.primary {
    background-color: var(--color-surface);
    color: var(--color-primary-dark);
}

.btn-download-banner.outline {
    background-color: var(--color-primary-dark);
    color: var(--color-text-light);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.btn-text-wrap {
    display: flex;
    flex-direction: column;
    line-height: 1.2;
}

.btn-small-label {
    font-size: 0.65rem;
    letter-spacing: 0.06em;
    opacity: 0.8;
    font-weight: 600;
}

.btn-main-label {
    font-size: 0.92rem;
    font-weight: 700;
}

.download-specs {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 1.25rem;
    font-size: 0.82rem;
    color: rgba(255, 255, 255, 0.9);
    font-weight: 500;
}

.spec-item {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}

/* FAQ Section */
.section-faq {
    padding: 4.5rem 0 5.5rem;
    background-color: var(--color-bg-light);
    border-top: 1px solid var(--color-border);
}

.faq-container {
    max-width: 820px;
}

.faq-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.faq-item {
    background-color: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: 0.65rem;
    overflow: hidden;
}

.faq-item.open {
    border-color: var(--color-primary);
}

.faq-question {
    width: 100%;
    padding: 1.1rem 1.25rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    text-align: left;
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--color-text-primary);
}

.faq-chevron {
    color: var(--color-text-secondary);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.faq-answer {
    padding: 0 1.25rem 1.15rem;
    color: var(--color-text-secondary);
    font-size: 0.9rem;
    border-top: 1px solid var(--color-divider);
    padding-top: 0.85rem;
}

/* Footer */
.site-footer {
    background-color: var(--color-primary-dark);
    color: rgba(255, 255, 255, 0.84);
    padding: 3.5rem 0 1.75rem;
}

.footer-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 2.25rem;
    padding-bottom: 2rem;
}

@media (min-width: 640px) {
    .footer-grid {
        grid-template-columns: 1.6fr 1fr 1fr;
    }
}

@media (min-width: 960px) {
    .footer-grid {
        grid-template-columns: 1.8fr 1fr 1fr 1.1fr;
        gap: 3rem;
    }
}

.footer-brand {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    max-width: 28rem;
}

.footer-logo-badge {
    display: inline-flex;
    background-color: var(--color-surface);
    padding: 0.5rem 0.9rem;
    border-radius: 0.6rem;
    margin-bottom: 0.9rem;
}

.brand-logo-img.footer-img {
    height: 2.4rem;
}

.footer-brand-name {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--color-text-light);
    letter-spacing: -0.01em;
    margin-bottom: 0.4rem;
}

.footer-desc {
    font-size: 0.86rem;
    line-height: 1.55;
    color: rgba(255, 255, 255, 0.72);
    margin-bottom: 1.1rem;
}

.footer-col h4 {
    font-size: 0.82rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--color-text-light);
    margin-bottom: 1rem;
}

.footer-col ul {
    list-style: none;
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
    font-size: 0.88rem;
}

.footer-col a,
.footer-col span {
    color: rgba(255, 255, 255, 0.78);
    transition: color 0.15s ease;
}

.footer-col a:hover {
    color: var(--color-accent-light);
}

.footer-social {
    display: flex;
    gap: 0.6rem;
}

.social-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.4rem;
    height: 2.4rem;
    border-radius: 0.6rem;
    background-color: rgba(255, 255, 255, 0.08);
    color: rgba(255, 255, 255, 0.9);
    transition:
        background-color 0.15s ease,
        color 0.15s ease,
        transform 0.15s ease;
}

.social-btn:hover {
    background-color: var(--color-primary);
    color: var(--color-text-light);
    transform: translateY(-2px);
}

.footer-bottom {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(255, 255, 255, 0.12);
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 0.75rem;
    font-size: 0.78rem;
    color: rgba(255, 255, 255, 0.55);
}

/* Modal Placeholder for Download */
.modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 100;
    background-color: rgba(15, 23, 42, 0.55);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.25rem;
}

.modal-card {
    background-color: var(--color-surface);
    color: var(--color-text-primary);
    max-width: 460px;
    width: 100%;
    border-radius: 0.75rem;
    padding: 1.5rem;
    border: 1px solid var(--color-border);
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.75rem;
}

.modal-badge {
    background-color: var(--color-surface-variant);
    color: var(--color-primary);
    font-size: 0.7rem;
    font-weight: 700;
    padding: 0.22rem 0.6rem;
    border-radius: 0.3rem;
}

.modal-close {
    width: 1.9rem;
    height: 1.9rem;
    border-radius: 0.35rem;
    background-color: var(--color-surface-variant);
    color: var(--color-text-secondary);
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-card h3 {
    font-size: 1.15rem;
    font-weight: 800;
    margin-bottom: 0.55rem;
}

.modal-text {
    font-size: 0.88rem;
    color: var(--color-text-secondary);
    margin-bottom: 1.2rem;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
}

.btn-modal-close {
    background-color: var(--color-primary);
    color: var(--color-text-light);
    font-weight: 600;
    font-size: 0.86rem;
    padding: 0.55rem 1.15rem;
    border-radius: 0.45rem;
}

/* Floating WhatsApp Button */
.floating-wa-btn {
    position: fixed;
    bottom: 1.5rem;
    right: 1.5rem;
    z-index: 80;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background-color: #25d366;
    color: #ffffff;
    padding: 0.72rem 1.1rem;
    border-radius: 999px;
    font-weight: 600;
    font-size: 0.88rem;
    box-shadow: 0 6px 16px rgba(15, 23, 42, 0.16);
    transition: background-color 0.15s ease;
}

.floating-wa-btn:hover {
    background-color: #1ebe5d;
}

.wa-label {
    display: none;
}

@media (min-width: 640px) {
    .wa-label {
        display: inline;
    }
}

/* Try-On Lightbox */
.lightbox-backdrop {
    position: fixed;
    inset: 0;
    z-index: 120;
    background-color: rgba(15, 23, 42, 0.82);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.25rem;
}

.lightbox-content {
    display: flex;
    flex-direction: column;
    max-width: 900px;
    width: 100%;
    max-height: 92vh;
    border-radius: 0.75rem;
    overflow: hidden;
    background-color: var(--color-surface);
}

.lightbox-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--color-border);
}

.lightbox-title {
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--color-text-primary);
}

.lightbox-body {
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #0f172a;
    overflow: auto;
}

.lightbox-body img,
.lightbox-body video {
    max-width: 100%;
    max-height: 82vh;
    object-fit: contain;
    display: block;
}
</style>

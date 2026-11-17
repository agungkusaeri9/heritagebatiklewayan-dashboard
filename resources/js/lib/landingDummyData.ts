export type TryOnExample = {
    id: string;
    label: string;
    modelName: string;
    modelStats: string;
    modelPhoto: string;
    productName: string;
    productOrigin: string;
    productPrice: string;
    productPhoto: string;
    resultPhoto: string;
    resultVideoPoster: string;
    resultVideoUrl: string;
    videoDuration: string;
    caption: string;
};

export type CatalogProduct = {
    id: number;
    name: string;
    category: string;
    origin: string;
    price: string;
    originalPrice: string;
    rating: string;
    sold: string;
    badge: string;
    tryOnReady: boolean;
    imageUrl: string;
};

export type FaqItem = {
    question: string;
    answer: string;
};

export type StoreSettingPayload = {
    name: string;
    logo_url: string | null;
    description: string | null;
    contact: {
        phone: string | null;
        whatsapp: string | null;
        email: string | null;
        website: string | null;
    };
    social_media: {
        facebook_url: string | null;
        twitter_url: string | null;
        instagram_url: string | null;
    };
    address: string | null;
};

export type BannerPayload = {
    id: number;
    title: string;
    description: string | null;
    image_url: string | null;
    target_type: string;
    target_url: string | null;
    category: { id: number; name: string; slug: string } | null;
};

export type CategoryPayload = {
    id: number;
    name: string;
    slug: string;
    total_items: number;
};

export type ProductPayload = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    price: number;
    stock: number;
    sold_count: number;
    is_virtual_try_on: boolean;
    primary_image_url: string | null;
    category: { id: number; name: string; slug: string } | null;
};

export type LiveTryOnPayload = {
    id: number;
    product: {
        id: number;
        name: string;
        slug: string;
        price: number;
        sku: string | null;
        primary_image_url: string | null;
    } | null;
    virtual_model: {
        id: number;
        name: string;
        gender: string | null;
        photo_url: string | null;
    } | null;
    photo_url: string | null;
    video_url: string | null;
};

export type LatestAppVersion = {
    id: number;
    name: string;
    type: 'android' | 'ios';
    version: string;
    version_code: number;
    file_name: string | null;
    file_size: number | null;
    file_url: string | null;
    download_url: string | null;
    release_notes: string | null;
    is_latest: boolean;
    is_force_update: boolean;
    is_maintenance: boolean;
    maintenance_message: string | null;
};

export const FALLBACK_LANDING_IMG =
    'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?auto=format&fit=crop&w=600&q=80';

export const DEFAULT_WHATSAPP_NUMBER = '6281234567890';
export const DEFAULT_WHATSAPP_MESSAGE =
    'Halo Admin Arbatik, saya ingin bertanya mengenai produk batik dan pemesanan di aplikasi.';

export function formatRupiah(value: number): string {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(value));
}

export function formatSold(count: number): string {
    return `${count}+ terjual`;
}

export function mapProduct(p: ProductPayload): CatalogProduct {
    return {
        id: p.id,
        name: p.name,
        category: p.category?.name ?? 'Semua',
        origin: p.category?.name ?? '-',
        price: formatRupiah(p.price),
        originalPrice: '',
        rating: '',
        sold: p.sold_count > 0 ? formatSold(p.sold_count) : '',
        badge: p.is_virtual_try_on ? 'Bisa Try-On' : '',
        tryOnReady: p.is_virtual_try_on,
        imageUrl: p.primary_image_url || FALLBACK_LANDING_IMG,
    };
}

export function mapTryOnExample(t: LiveTryOnPayload): TryOnExample {
    return {
        id: String(t.id),
        label: t.product?.name ?? 'Produk',
        modelName: t.virtual_model?.name ?? 'Model',
        modelStats: '',
        modelPhoto: t.virtual_model?.photo_url ?? FALLBACK_LANDING_IMG,
        productName: t.product?.name ?? 'Produk Batik',
        productOrigin: '',
        productPrice: t.product ? formatRupiah(t.product.price) : '',
        productPhoto: t.product?.primary_image_url ?? FALLBACK_LANDING_IMG,
        resultPhoto: t.photo_url ?? FALLBACK_LANDING_IMG,
        resultVideoPoster: t.photo_url ?? FALLBACK_LANDING_IMG,
        resultVideoUrl: t.video_url ?? '',
        videoDuration: '00:05',
        caption: `Hasil try-on ${t.virtual_model?.name ?? 'model'} mengenakan ${t.product?.name ?? 'produk batik'}.`,
    };
}

export const tryOnExamples: TryOnExample[] = [
    {
        id: 'contoh-1',
        label: 'Contoh 1 • Kemeja Pria',
        modelName: 'Raka Pratama (Foto User/Model)',
        modelStats: 'Tinggi 175 cm • Berat 68 kg',
        modelPhoto:
            'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=600&q=80',
        productName: 'Kemeja Sogan Parang Kusumo',
        productOrigin: 'Batik Tulis Solo',
        productPrice: 'Rp 485.000',
        productPhoto:
            'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?auto=format&fit=crop&w=600&q=80',
        resultPhoto:
            'https://images.unsplash.com/photo-1617137984095-74e4e5e3613f?auto=format&fit=crop&w=700&q=80',
        resultVideoPoster:
            'https://images.unsplash.com/photo-1617137984095-74e4e5e3613f?auto=format&fit=crop&w=700&q=80',
        resultVideoUrl: 'https://www.w3schools.com/html/mov_bbb.mp4',
        videoDuration: '00:05',
        caption:
            'Dari 1 foto diri dan 1 foto kemeja batik, aplikasi menghasilkan tampilan foto saat dikenakan sekaligus video gerakan 5 detik.',
    },
    {
        id: 'contoh-2',
        label: 'Contoh 2 • Blouse Wanita',
        modelName: 'Nadia Sekar (Foto User/Model)',
        modelStats: 'Tinggi 164 cm • Berat 52 kg',
        modelPhoto:
            'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=600&q=80',
        productName: 'Blouse Kutubaru Kawung Kencana',
        productOrigin: 'Batik Cap Yogyakarta',
        productPrice: 'Rp 425.000',
        productPhoto:
            'https://images.unsplash.com/photo-1551163943-3f6a855d1153?auto=format&fit=crop&w=600&q=80',
        resultPhoto:
            'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&w=700&q=80',
        resultVideoPoster:
            'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&w=700&q=80',
        resultVideoUrl: 'https://www.w3schools.com/html/mov_bbb.mp4',
        videoDuration: '00:05',
        caption:
            'Potongan blouse dan warna motif Kawung terlihat jelas menyatu pada postur model baik pada hasil foto maupun video 5 detik.',
    },
    {
        id: 'contoh-3',
        label: 'Contoh 3 • Outer Batik',
        modelName: 'Dimas Aditya (Foto User/Model)',
        modelStats: 'Tinggi 172 cm • Berat 65 kg',
        modelPhoto:
            'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=600&q=80',
        productName: 'Outer Heritage Mega Mendung Sogan',
        productOrigin: 'Batik Kombinasi Cirebon',
        productPrice: 'Rp 550.000',
        productPhoto:
            'https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?auto=format&fit=crop&w=600&q=80',
        resultPhoto:
            'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&w=700&q=80',
        resultVideoPoster:
            'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&w=700&q=80',
        resultVideoUrl: 'https://www.w3schools.com/html/mov_bbb.mp4',
        videoDuration: '00:05',
        caption:
            'Memudahkan pembeli melihat jatuhnya kain outer batik saat dipakai berdiri maupun bergerak selama 5 detik.',
    },
];

export const catalogProducts: CatalogProduct[] = [
    {
        id: 1,
        name: 'Kemeja Batik Tulis Sogan Parang Ratu',
        category: 'Pria',
        origin: 'Solo',
        price: 'Rp 680.000',
        originalPrice: 'Rp 790.000',
        rating: '4.9',
        sold: '320+ terjual',
        badge: 'Batik Tulis',
        tryOnReady: true,
        imageUrl:
            'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?auto=format&fit=crop&w=600&q=80',
    },
    {
        id: 2,
        name: 'Blouse Kutubaru Modern Kawung Sari',
        category: 'Wanita',
        origin: 'Yogyakarta',
        price: 'Rp 445.000',
        originalPrice: 'Rp 520.000',
        rating: '4.9',
        sold: '510+ terjual',
        badge: 'Terlaris',
        tryOnReady: true,
        imageUrl:
            'https://images.unsplash.com/photo-1551163943-3f6a855d1153?auto=format&fit=crop&w=600&q=80',
    },
    {
        id: 3,
        name: 'Set Couple Sarimbit Sogan Prameswari',
        category: 'Sarimbit',
        origin: 'Solo',
        price: 'Rp 890.000',
        originalPrice: 'Rp 1.050.000',
        rating: '5.0',
        sold: '215+ terjual',
        badge: 'Koleksi Eksklusif',
        tryOnReady: true,
        imageUrl:
            'https://images.unsplash.com/photo-1617137984095-74e4e5e3613f?auto=format&fit=crop&w=600&q=80',
    },
    {
        id: 4,
        name: 'Kemeja Lengan Pendek Truntum Kencana',
        category: 'Pria',
        origin: 'Pekalongan',
        price: 'Rp 365.000',
        originalPrice: 'Rp 430.000',
        rating: '4.8',
        sold: '440+ terjual',
        badge: 'Batik Cap',
        tryOnReady: true,
        imageUrl:
            'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?auto=format&fit=crop&w=600&q=80',
    },
    {
        id: 5,
        name: 'Outer Kimono Batik Sogan Sekar Jagad',
        category: 'Wanita',
        origin: 'Lasem',
        price: 'Rp 495.000',
        originalPrice: 'Rp 580.000',
        rating: '4.9',
        sold: '290+ terjual',
        badge: 'Edisi Terbatas',
        tryOnReady: true,
        imageUrl:
            'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&w=600&q=80',
    },
    {
        id: 6,
        name: 'Sarimbit Keluarga Keraton Sogan Emas',
        category: 'Sarimbit',
        origin: 'Yogyakarta',
        price: 'Rp 950.000',
        originalPrice: 'Rp 1.150.000',
        rating: '4.9',
        sold: '180+ terjual',
        badge: 'Premium Heritage',
        tryOnReady: true,
        imageUrl:
            'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?auto=format&fit=crop&w=600&q=80',
    },
];

export const faqItems: FaqItem[] = [
    {
        question: 'Bagaimana cara kerja fitur Virtual Try-On di aplikasi Arbatik?',
        answer:
            'Sangat mudah! Anda cukup mengunggah foto diri Anda (atau memilih foto model yang tersedia), lalu pilih foto produk baju batik yang ingin dicoba. Sistem AI kami akan memadukan keduanya dan menghasilkan tampilan Anda mengenakan batik tersebut dalam bentuk Foto maupun Video 5 Detik.',
    },
    {
        question: 'Apa fungsi dari hasil Virtual Try-On berupa Video 5 Detik?',
        answer:
            'Selain menghasilkan foto, aplikasi Arbatik juga menampilkan video singkat berdurasi 5 detik sehingga Anda bisa melihat secara lebih jelas bagaimana jatuhnya kain batik, detail motif, dan keserasian warna baju saat dikenakan bergerak.',
    },
    {
        question: 'Apakah seluruh produk batik di Arbatik asli dari pengrajin lokal?',
        answer:
            'Ya, 100% koleksi kami dikurasi langsung dari pengrajin dan rumah batik terpercaya di Solo, Yogyakarta, Pekalongan, Cirebon, hingga Lasem. Setiap produk dilengkapi informasi jenis batik (Batik Tulis, Batik Cap, maupun Kombinasi) serta daerah asalnya secara jujur.',
    },
    {
        question: 'Bagaimana cara pembayaran dan konfirmasi pesanan di aplikasi Arbatik?',
        answer:
            'Pembayaran dilakukan secara mudah dan aman melalui transfer bank. Setelah melakukan transfer, Anda cukup mengunggah foto bukti pembayaran langsung di dalam aplikasi Arbatik. Anda juga bisa menggunakan fitur Chat di aplikasi atau menghubungi WhatsApp kami untuk konsultasi ukuran maupun konfirmasi pesanan.',
    },
    {
        question: 'Bagaimana cara mengunduh aplikasi belanja Arbatik di HP saya?',
        answer:
            'Anda cukup menekan tombol "Download untuk Android" di halaman ini untuk mengunduh aplikasi resmi toko Arbatik secara gratis, lalu pasang di smartphone Anda dan mulai berbelanja.',
    },
];

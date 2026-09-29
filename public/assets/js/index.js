// Index page specific functionality

document.addEventListener('DOMContentLoaded', function() {
    // Initialize pipeline visualization
    initPipeline();
    
    // Initialize accordion content
    initAccordion();
});

function initPipeline() {
    const pipeline = document.getElementById('heroPipeline');
    if (!pipeline) return;

    const steps = [
        { name: 'Citra Asli', icon: '📷' },
        { name: 'DCT', icon: '🔄' },
        { name: 'Embed', icon: '💧' },
        { name: 'IDCT', icon: '🔃' },
        { name: 'Watermarked', icon: '✅' }
    ];

    pipeline.innerHTML = steps.map((step, index) => {
        const arrow = index < steps.length - 1 ? '<span class="parrow"></span>' : '';
        return `
            <div class="pnode">
                <div class="box">${step.icon}</div>
                <span>${step.name}</span>
            </div>
            ${arrow}
        `;
    }).join('');
}

function initAccordion() {
    const accWrap = document.getElementById('accWrap');
    if (!accWrap) return;

    const faqs = [
        {
            question: 'Apa itu DCT (Discrete Cosine Transform)?',
            answer: 'DCT adalah transformasi matematis yang mengubah data piksel dari domain spasial (nilai piksel) ke domain frekuensi (koefisien frekuensi). Dalam watermarking, DCT digunakan untuk menyisipkan watermark di koefisien frekuensi menengah yang tidak terlalu terlihat oleh mata manusia.'
        },
        {
            question: 'Apa itu Blind Watermarking?',
            answer: 'Blind watermarking adalah teknik ekstraksi watermark yang tidak membutuhkan citra asli. Sistem hanya membutuhkan citra ter-watermark dan secret key untuk memulihkan watermark yang tersisip.'
        },
        {
            question: 'Apa fungsi Secret Key?',
            answer: 'Secret key menentukan urutan blok pseudo-acak tempat watermark disisipkan. Key yang sama digunakan saat embedding dan extraction. Tanpa key yang sama, ekstraksi tidak dapat dilakukan.'
        },
        {
            question: 'Apa itu PSNR dan SSIM?',
            answer: 'PSNR (Peak Signal-to-Noise Ratio) mengukur kualitas visual citra ter-watermark dibanding citra asli. SSIM (Structural Similarity Index) mengukur kemiripan struktur visual. Keduanya digunakan untuk menilai imperceptibility watermark.'
        },
        {
            question: 'Apa itu NC dan BER?',
            answer: 'NC (Normalized Correlation) mengukur kemiripan watermark asli dengan watermark hasil ekstraksi. BER (Bit Error Rate) menghitung persentase bit yang salah dibaca. Keduanya mengukur robustness watermark setelah serangan.'
        },
        {
            question: 'Apa itu Redundancy dalam watermarking?',
            answer: 'Redundancy adalah jumlah salinan blok yang digunakan untuk menanam bit watermark yang sama. Redundancy > 1 memungkinkan majority vote saat extraction, meningkatkan ketahanan terhadap cropping.'
        }
    ];

    accWrap.innerHTML = faqs.map((faq, index) => `
        <div class="acc-item">
            <button class="acc-btn" data-index="${index}">
                ${faq.question}
                <span class="plus">+</span>
            </button>
            <div class="acc-panel">
                <p>${faq.answer}</p>
            </div>
        </div>
    `).join('');

    // Add click handlers
    accWrap.querySelectorAll('.acc-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const panel = this.nextElementSibling;
            const plus = this.querySelector('.plus');
            
            // Close all other panels
            document.querySelectorAll('.acc-panel').forEach(p => {
                if (p !== panel) {
                    p.style.maxHeight = null;
                    p.previousElementSibling.querySelector('.plus').textContent = '+';
                }
            });
            
            // Toggle current panel
            if (panel.style.maxHeight) {
                panel.style.maxHeight = null;
                plus.textContent = '+';
            } else {
                panel.style.maxHeight = panel.scrollHeight + 'px';
                plus.textContent = '−';
            }
        });
    });
}

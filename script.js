// --- Lógica da Galeria de Fotos (ATUALIZADA para Swiper.js) ---
async function loadPhotos() {
    // Usa o ID do WRAPPER do Swiper agora
    const swiperWrapper = document.getElementById('photoGallerySwiperWrapper');

    if (!swiperWrapper) {
        console.error("Container '#photoGallerySwiperWrapper' não encontrado.");
        // Exibe erro dentro do container principal do Swiper se ele existir
        const swiperContainer = document.querySelector('.photo-swiper-container');
        if (swiperContainer) swiperContainer.innerHTML = '<p>Erro ao inicializar galeria.</p>';
        return;
    }

    swiperWrapper.innerHTML = '<div class="swiper-slide"><p>Carregando fotos...</p></div>'; // Limpa e mostra carregando

    try {
        const response = await fetch('list_files.php');
        if (!response.ok) throw new Error(`Erro HTTP: ${response.status}`);
        const data = await response.json();
        if (data.error) throw new Error(`Erro servidor: ${data.error}`);

        const imageFiles = data.files || [];
        swiperWrapper.innerHTML = ''; // Limpa 'Carregando'

        if (imageFiles.length === 0) {
            swiperWrapper.innerHTML = '<div class="swiper-slide"><p>Nenhuma foto na galeria.</p></div>';
            // Destruir instância anterior do Swiper se existir, para evitar erros
            if (swiperWrapper.swiper) {
               swiperWrapper.swiper.destroy(true, true);
            }
            return;
        }

        // Limita a 10 fotos (mais recentes primeiro, pela ordenação do PHP)
        const imagesToShow = imageFiles.slice(0, 10);

        // Cria e adiciona cada slide
        imagesToShow.forEach(imageUrl => {
            const slideDiv = document.createElement('div');
            slideDiv.className = 'swiper-slide'; // Classe para o slide

            const img = document.createElement('img');
            img.src = imageUrl;
            img.alt = "Foto da Pelada";
            img.loading = "lazy";

            slideDiv.appendChild(img);
            swiperWrapper.appendChild(slideDiv);
        });

        // (Re)Inicializa o Swiper DEPOIS de adicionar os slides
        // Destruir instância anterior antes de criar uma nova, caso a galeria seja recarregada
        if (swiperWrapper.swiper) {
            swiperWrapper.swiper.destroy(true, true);
        }
        // Cria a nova instância
         new Swiper('.photo-swiper-container', {
            // Opções do Swiper
            loop: true, // Faz o carrossel voltar ao início
            autoplay: {
                delay: 3000, // Tempo em milissegundos (3 segundos)
                disableOnInteraction: false, // Continua mesmo se usuário interagir
            },
            slidesPerView: 1, // Quantos slides visíveis por vez (padrão)
            spaceBetween: 15, // Espaço entre slides (se tiver mais de 1 visível)
             // Breakpoints para responsividade (opcional)
             breakpoints: {
               // Quando a largura da tela for >= 640px
               640: {
                 slidesPerView: 2, // Mostra 2 slides
                 spaceBetween: 20
               },
               // Quando a largura da tela for >= 768px (pode remover se já tiver 2)
               // 768: {
               //   slidesPerView: 3, // Mostra 3 slides
               //   spaceBetween: 30
               // }
             },

            // Paginação (bolinhas)
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },

            // Navegação (setas)
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
        });


        if (imageFiles.length > 10) { console.log(`Mostrando 10 de ${imageFiles.length} fotos.`); }

    } catch (error) {
        console.error("Erro ao carregar fotos da galeria:", error);
        swiperWrapper.innerHTML = `<div class="swiper-slide"><p>Erro ao carregar fotos: ${error.message}.</p></div>`;
         // Destruir instância anterior do Swiper se existir
        if (swiperWrapper.swiper) {
            swiperWrapper.swiper.destroy(true, true);
        }
    }
}
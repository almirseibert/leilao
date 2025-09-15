// Este arquivo gerencia toda a lógica do lado do cliente e a comunicação com a API PHP.

const mainContent = document.getElementById('main-content');
const auctionsContainer = document.getElementById('auctions-container');
const loadingMessage = document.getElementById('loading');
const backButton = document.getElementById('back-button');
const authModal = document.getElementById('auth-modal');
const closeAuthModalButton = document.getElementById('close-modal');
const loginForm = document.getElementById('login-form');
const registerForm = document.getElementById('register-form');
const showRegisterLink = document.getElementById('show-register');
const showLoginLink = document.getElementById('show-login');
const loginButtonNav = document.getElementById('login-button-nav');
const sellButtonNav = document.getElementById('sell-button-nav');
const myBidsButton = document.getElementById('my-bids-button');
const mySalesButton = document.getElementById('my-sales-button');
const userInfoNav = document.getElementById('user-info-nav');
const userDisplayName = document.getElementById('user-display-name');
const logoutButtonNav = document.getElementById('logout-button-nav');
const authErrorMessage = document.getElementById('auth-error-message');
const pixModal = document.getElementById('pix-modal');
const closePixModalButton = document.getElementById('close-pix-modal');
const copyPixButton = document.getElementById('copy-pix-button');
const pixCodeElement = document.getElementById('pix-code');
const toast = document.getElementById('notification-toast');
const toastMessage = document.getElementById('toast-message');

// Funções de navegação e modal
function showPage(page) {
    window.location.href = `index.php?page=${page}`;
}
function showAuctionDetail(id) {
    window.location.href = `index.php?auction_id=${id}`;
}
function showAuthModal() { authModal.classList.remove('hidden'); }
function closeAuthModal() { authModal.classList.add('hidden'); }
function showPixModal() { pixModal.classList.remove('hidden'); }
function closePixModal() { pixModal.classList.add('hidden'); }
function toggleAuthForm() {
    loginForm.classList.toggle('hidden');
    registerForm.classList.toggle('hidden');
    authErrorMessage.classList.add('hidden');
}
function showToast(message) {
    toastMessage.textContent = message;
    toast.classList.remove('hidden');
    setTimeout(() => {
        toast.classList.add('hidden');
    }, 5000);
}

// Eventos de navegação
if (backButton) backButton.addEventListener('click', () => history.back());
if (sellButtonNav) {
    sellButtonNav.addEventListener('click', (e) => {
        if (!IS_AUTHENTICATED) { e.preventDefault(); showAuthModal(); toggleAuthForm(); }
    });
}
if (myBidsButton) {
    myBidsButton.addEventListener('click', (e) => {
        if (!IS_AUTHENTICATED) { e.preventDefault(); showAuthModal(); }
    });
}
if (mySalesButton) {
    mySalesButton.addEventListener('click', (e) => {
        if (!IS_AUTHENTICATED) { e.preventDefault(); showAuthModal(); }
    });
}
if (loginButtonNav) loginButtonNav.addEventListener('click', (e) => { e.preventDefault(); showAuthModal(); });
if (logoutButtonNav) {
    logoutButtonNav.addEventListener('click', async () => {
        const res = await fetch('api.php?action=logout');
        const data = await res.json();
        if (data.success) { window.location.href = 'index.php'; }
    });
}
if (closeAuthModalButton) closeAuthModalButton.addEventListener('click', closeAuthModal);
if (closePixModalButton) closePixModalButton.addEventListener('click', closePixModal);
if (showRegisterLink) showRegisterLink.addEventListener('click', (e) => { e.preventDefault(); toggleAuthForm(); });
if (showLoginLink) showLoginLink.addEventListener('click', (e) => { e.preventDefault(); toggleAuthForm(); });

// Funções de formatação e renderização
function formatTime(ms) {
    const totalSeconds = Math.floor(ms / 1000);
    const days = Math.floor(totalSeconds / (3600 * 24));
    const hours = Math.floor((totalSeconds % (3600 * 24)) / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;
    let timeStr = "";
    if (days > 0) timeStr += `${days}d `;
    if (hours > 0) timeStr += `${hours}h `;
    timeStr += `${minutes}m ${seconds}s`;
    return timeStr.trim();
}

function renderAuctions(auctions) {
    const container = document.getElementById('auctions-container');
    if (!container) return;
    if (auctions.length === 0) {
        container.innerHTML = '<p class="text-center text-gray-400 text-lg">Nenhum leilão disponível no momento.</p>';
    } else {
        container.innerHTML = auctions.map(auction => `
            <div class="product-card" data-doc-id="${auction.id}" data-end-time="${auction.end_time}">
                <img src="https://placehold.co/600x400/D3D3D3/000000?text=Sem+Foto" alt="${auction.product_name}" class="w-full h-48 object-cover">
                <div class="p-4">
                    <h3 class="font-semibold text-lg text-gray-200">${auction.product_name}</h3>
                    <p class="text-gray-400 text-sm mt-1">Categoria: ${auction.category}</p>
                    <p class="text-gray-400 text-sm">Vendedor: ${auction.seller_name}</p>
                    <p class="text-xl font-bold text-green-500 mt-2">Lance Atual: R$ ${parseFloat(auction.current_bid).toFixed(2).replace('.', ',')}</p>
                    <p class="text-gray-400 text-sm mt-1">${auction.bids} lance${auction.bids > 1 ? 's' : ''}</p>
                    <div id="countdown-${auction.id}" class="countdown-timer timer-green"></div>
                </div>
            </div>
        `).join('');
        container.querySelectorAll('.product-card').forEach(card => {
            card.addEventListener('click', () => {
                showAuctionDetail(card.dataset.docId);
            });
        });
        updateAllCountdowns();
    }
}

function updateAllCountdowns() {
    const cards = document.querySelectorAll('.product-card');
    cards.forEach(card => {
        const timerElement = card.querySelector('.countdown-timer');
        const endTime = new Date(card.dataset.end_time).getTime();
        const now = Date.now();
        const distance = endTime - now;
        if (distance < 0) {
            timerElement.innerHTML = "Leilão Encerrado!";
            timerElement.classList.remove('timer-green', 'timer-yellow', 'timer-red');
            timerElement.classList.add('bg-gray-400', 'text-gray-800');
            return;
        }
        timerElement.innerHTML = formatTime(distance);
        if (distance < 1000 * 60 * 5) {
            timerElement.classList.add('timer-red');
            timerElement.classList.remove('timer-green', 'timer-yellow');
        } else if (distance < 1000 * 60 * 30) {
            timerElement.classList.add('timer-yellow');
            timerElement.classList.remove('timer-green', 'timer-red');
        } else {
            timerElement.classList.add('timer-green');
            timerElement.classList.remove('timer-yellow', 'timer-red');
        }
    });
}

// Lógica para carregar o conteúdo da página com base na URL
const urlParams = new URLSearchParams(window.location.search);
const currentPage = urlParams.get('page') || (urlParams.has('auction_id') ? 'detail' : 'home');

async function loadPageContent() {
    if (currentPage === 'home') {
        const res = await fetch('api.php?action=list_auctions');
        const data = await res.json();
        if (data.success) {
            renderAuctions(data.auctions);
        }
        if (loadingMessage) loadingMessage.classList.add('hidden');
        setInterval(updateAllCountdowns, 1000);
    } else if (currentPage === 'detail') {
        const auctionId = urlParams.get('auction_id');
        const res = await fetch(`api.php?action=get_auction_detail&id=${auctionId}`);
        const data = await res.json();
        if (data.success && data.auction) {
            renderProductDetail(data.auction);
        } else {
            document.getElementById('product-detail-content').innerHTML = '<p class="text-center text-gray-400 text-lg">Leilão não encontrado.</p>';
        }
    } else if (currentPage === 'compras') {
        const res = await fetch('api.php?action=my_bids');
        const data = await res.json();
        if (data.success) {
            renderMyBids(data.auctions);
        }
    } else if (currentPage === 'vendas') {
        const res = await fetch('api.php?action=my_sales');
        const data = await res.json();
        if (data.success) {
            renderMySales(data.auctions);
        }
    }
}

// Lógica de autenticação
if (document.getElementById('register-button')) {
    document.getElementById('register-button').addEventListener('click', async () => {
        console.log('Botão de Cadastro Clicado.');
        const name = document.getElementById('register-name').value;
        const email = document.getElementById('register-email').value;
        const password = document.getElementById('register-password').value;
        try {
            const res = await fetch('api.php?action=register', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, email, password })
            });
            const data = await res.json();
            console.log('Resposta da API:', data);
            if (data.success) {
                window.location.href = 'index.php';
            } else {
                authErrorMessage.textContent = data.message;
                authErrorMessage.classList.remove('hidden');
            }
        } catch (error) {
            console.error('Erro na requisição:', error);
            authErrorMessage.textContent = 'Erro de conexão. Verifique sua conexão ou a URL da API.';
            authErrorMessage.classList.remove('hidden');
        }
    });
}
if (document.getElementById('login-button')) {
    document.getElementById('login-button').addEventListener('click', async () => {
        const email = document.getElementById('login-email').value;
        const password = document.getElementById('login-password').value;
        try {
            const res = await fetch('api.php?action=login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, password })
            });
            const data = await res.json();
            console.log('Resposta da API:', data);
            if (data.success) {
                window.location.href = 'index.php';
            } else {
                authErrorMessage.textContent = data.message;
                authErrorMessage.classList.remove('hidden');
            }
        } catch (error) {
            console.error('Erro na requisição:', error);
            authErrorMessage.textContent = 'Erro de conexão. Verifique sua conexão ou a URL da API.';
            authErrorMessage.classList.remove('hidden');
        }
    });
}

// Lógica para o formulário de anúncio (dentro de vender)
if (document.getElementById('vender')) {
    const minPriceCheck = document.getElementById('min-price-check');
    const minPriceSection = document.getElementById('min-price-section');
    const minPriceInput = document.getElementById('min-price');
    const startPriceInput = document.getElementById('start-price');
    const feeMessage = document.getElementById('fee-message');
    const submitButton = document.getElementById('submit-ad');
    const validationError = document.getElementById('validation-error');
    
    // Validação de dados antes de enviar
    function validateProductData() {
        const name = document.getElementById('product-name').value.trim();
        const description = document.getElementById('product-description').value.trim();
        const category = document.getElementById('product-category').value;
        const condition = document.getElementById('product-condition').value;
        const functionalCondition = document.getElementById('functional-condition').value;
        const endDate = document.getElementById('auction-end-date').value;

        if (!name || !description || !category || !condition || !functionalCondition || !endDate) {
            validationError.textContent = 'Por favor, preencha todos os campos obrigatórios.';
            validationError.classList.remove('hidden');
            return false;
        }

        const now = new Date();
        const endTime = new Date(endDate);
        const diffInDays = (endTime - now) / (1000 * 60 * 60 * 24);
        if (diffInDays < 7 || diffInDays > 30) {
            validationError.textContent = 'A data de término do leilão deve ser entre 7 e 30 dias.';
            validationError.classList.remove('hidden');
            return false;
        }

        if (minPriceCheck.checked) {
            const minPrice = parseFloat(minPriceInput.value);
            const startPrice = parseFloat(startPriceInput.value);
            if (isNaN(minPrice) || isNaN(startPrice) || startPrice > (minPrice * 0.5)) {
                validationError.textContent = 'O lance inicial não pode ser superior a 50% do valor mínimo.';
                validationError.classList.remove('hidden');
                return false;
            }
        }
        
        validationError.classList.add('hidden');
        return true;
    }

    minPriceCheck.addEventListener('change', (e) => {
        minPriceSection.classList.toggle('hidden', !e.target.checked);
    });

    minPriceInput.addEventListener('input', () => {
        let minPrice = parseFloat(minPriceInput.value);
        let fee = 0;
        if (isNaN(minPrice) || minPrice <= 0) {
            feeMessage.textContent = "Digite um valor para calcular a taxa de anúncio.";
            return;
        }
        if (minPrice <= 100) { fee = 10; } 
        else if (minPrice <= 250) { fee = 25; } 
        else if (minPrice <= 500) { fee = 50; } 
        else if (minPrice <= 1000) { fee = 100; } 
        else if (minPrice <= 2000) { fee = 150; } 
        else if (minPrice <= 5000) { fee = 300; } 
        else { fee = minPrice * 0.05; }
        feeMessage.textContent = `A taxa de anúncio será de R$ ${fee.toFixed(2)}.`;
    });

    submitButton.addEventListener('click', async () => {
        if (!validateProductData()) {
            return;
        }
        
        const productData = {
            name: document.getElementById('product-name').value,
            description: document.getElementById('product-description').value,
            category: document.getElementById('product-category').value,
            condition: document.getElementById('product-condition').value,
            market_value: parseFloat(document.getElementById('product-market-value').value) || 0,
            fipe_value: parseFloat(document.getElementById('fipe-value').value) || 0,
            functional_condition: document.getElementById('functional-condition').value,
            has_min_price: minPriceCheck.checked,
            min_price: parseFloat(minPriceInput.value) || 0,
            start_price: parseFloat(startPriceInput.value) || 1,
            end_time: document.getElementById('auction-end-date').value,
            bids: 0
        };

        console.log("Dados a serem enviados:", productData);
        
        try {
            const res = await fetch('api.php?action=create_auction', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(productData)
            });
            const data = await res.json();
            console.log("Resposta do servidor:", data);
            
            if (data.success) {
                showToast(data.message);
                window.location.href = 'index.php';
            } else {
                validationError.textContent = data.message;
                validationError.classList.remove('hidden');
            }
        } catch (error) {
            console.error('Erro na requisição:', error);
            validationError.textContent = 'Erro de conexão com o servidor. Tente novamente.';
            validationError.classList.remove('hidden');
        }
    });
}

async function renderProductDetail(auction) {
    const detailContent = document.getElementById('product-detail-content');
    if (!detailContent) return;
    
    // Converte a data de término do leilão para o formato JavaScript
    const endTime = new Date(auction.end_time.replace(/-/g, '/'));

    detailContent.innerHTML = `
        <div class="w-full md:w-1/2">
            <img src="https://placehold.co/800x600/D3D3D3/000000?text=Sem+Foto" alt="${auction.product_name}" class="w-full rounded-lg shadow-md">
        </div>
        <div class="w-full md:w-1/2">
            <h2 class="text-3xl font-bold text-gray-200 mb-2">${auction.product_name}</h2>
            <p class="text-gray-400 mb-4">${auction.description}</p>
            <div class="grid grid-cols-2 gap-4 text-gray-400 mb-6">
                <p><strong>Categoria:</strong> ${auction.category}</p>
                <p><strong>Condição:</strong> ${auction.condition}</p>
                <p><strong>Valor de Mercado:</strong> R$ ${parseFloat(auction.market_value).toFixed(2).replace('.', ',')}</p>
                ${auction.fipe_value > 0 ? `<p><strong>Valor Tabela FIPE:</strong> R$ ${parseFloat(auction.fipe_value).toFixed(2).replace('.', ',')}</p>` : ''}
                <p><strong>Estado:</strong> ${auction.functional_condition}</p>
            </div>
            <div class="bg-gray-700 p-4 rounded-lg mb-6">
                <div id="detail-countdown" class="countdown-timer timer-green text-xl mb-4"></div>
                <p class="text-2xl font-bold text-green-500">Lance Atual: R$ <span id="current-bid">${parseFloat(auction.current_bid).toFixed(2).replace('.', ',')}</span></p>
                <p class="text-gray-400">Total de lances: <span id="bid-count">${auction.bids}</span></p>
                <div id="bid-input-container" class="mt-4 ${IS_AUTHENTICATED ? '' : 'hidden'}">
                    <input type="number" id="bid-amount" placeholder="Seu lance (R$)" class="w-full p-3 border rounded-lg text-gray-200 bg-gray-600 border-gray-500">
                    <button id="place-bid-button" class="mt-2 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg w-full">Fazer Lance</button>
                    <p id="bid-error-message" class="text-red-400 text-sm mt-2"></p>
                </div>
                <div id="auction-ended-message" class="mt-4 hidden">
                     <p class="text-lg font-bold text-red-500 text-center">Leilão Encerrado!</p>
                     <div id="winner-info" class="mt-2 text-center text-gray-400"></div>
                     <button id="pay-now-button" class="mt-4 bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg w-full hidden">Pagar Agora</button>
                </div>
            </div>
        </div>
    `;
    updateCountdownDetail(endTime, auction.id);
    
    // Lógica para o lance e o botão de pagamento
    const placeBidButton = document.getElementById('place-bid-button');
    if (placeBidButton) {
        placeBidButton.addEventListener('click', async () => {
            const bidAmountInput = document.getElementById('bid-amount');
            const bidAmount = parseFloat(bidAmountInput.value);
            const res = await fetch('api.php?action=place_bid', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ auction_id: auction.id, bid_amount: bidAmount })
            });
            const data = await res.json();
            if (data.success) {
                showToast(data.message);
                bidAmountInput.value = '';
            } else {
                document.getElementById('bid-error-message').textContent = data.message;
            }
        });
    }

    const payNowButton = document.getElementById('pay-now-button');
    if (payNowButton) {
        payNowButton.addEventListener('click', async () => {
            showPixModal();
            const res = await fetch('api.php?action=pay_now', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ auction_id: auction.id })
            });
            const data = await res.json();
            if (data.success) { showToast('Pagamento simulado. O valor está retido.'); }
        });
    }
}

function updateCountdownDetail(endTime, auctionId) {
    const timerElement = document.getElementById('detail-countdown');
    if (!timerElement) return;

    setInterval(async () => {
        const now = Date.now();
        const distance = endTime.getTime() - now;
        if (distance < 0) {
            timerElement.innerHTML = "Leilão Encerrado!";
            timerElement.classList.remove('timer-green', 'timer-yellow', 'timer-red');
            timerElement.classList.add('bg-gray-400', 'text-gray-800');
            
            const res = await fetch(`api.php?action=get_auction_detail&id=${auctionId}`);
            const data = await res.json();
            if (data.success && data.auction) {
                const auction = data.auction;
                const winnerInfo = document.getElementById('winner-info');
                const payNowButton = document.getElementById('pay-now-button');
                
                if (auction.highest_bidder_id == USER_ID) {
                    winnerInfo.innerHTML = `<p class="text-green-500 font-bold">Parabéns! Você venceu o leilão!</p>`;
                    if (auction.payment_status === 'Aguardando Pagamento') {
                        payNowButton.classList.remove('hidden');
                    }
                } else {
                    winnerInfo.innerHTML = `<p class="text-red-500 font-bold">Leilão Vencido por outro usuário.</p>`;
                }
            }
            return;
        }
        timerElement.innerHTML = formatTime(distance);
        if (distance < 1000 * 60 * 5) {
            timerElement.classList.add('timer-red');
            timerElement.classList.remove('timer-green', 'timer-yellow');
        } else if (distance < 1000 * 60 * 30) {
            timerElement.classList.add('timer-yellow');
            timerElement.classList.remove('timer-green', 'timer-red');
        } else {
            timerElement.classList.add('timer-green');
            timerElement.classList.remove('timer-yellow', 'timer-red');
        }
    }, 1000);
}

function renderMyBids(auctions) {
    const container = document.getElementById('my-bids-container');
    if (!container) return;
    if (auctions.length === 0) {
        container.innerHTML = `<p class="text-center text-gray-400 text-lg">Você ainda não venceu nenhum leilão.</p>`;
        return;
    }
    container.innerHTML = auctions.map(auction => `
        <div class="bg-gray-800 p-4 rounded-lg shadow-md">
            <h3 class="font-bold text-lg text-gray-200">${auction.product_name}</h3>
            <p class="text-gray-400 mt-1">Lance Vencedor: R$ ${parseFloat(auction.current_bid).toFixed(2).replace('.', ',')}</p>
            <p class="mt-2 font-semibold">Status do Pagamento: <span class="${auction.payment_status ? 'text-green-500' : 'text-red-500'}">${auction.payment_status}</span></p>
            ${auction.payment_status === 'Aguardando confirmação de recebimento' ? 
                `<button class="mt-4 bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg receive-button" data-id="${auction.id}">Confirmar Recebimento</button>` : ''}
        </div>
    `).join('');
    
    container.querySelectorAll('.receive-button').forEach(button => {
        button.addEventListener('click', async (e) => {
            const auctionId = e.target.dataset.id;
            const res = await fetch('api.php?action=confirm_receipt', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ auction_id: auctionId })
            });
            const data = await res.json();
            if (data.success) { showToast(data.message); loadPageContent(); } else { showToast(data.message); }
        });
    });
}

function renderMySales(auctions) {
    const container = document.getElementById('my-sales-container');
    if (!container) return;
    if (auctions.length === 0) {
        container.innerHTML = `<p class="text-center text-gray-400 text-lg">Você ainda não realizou vendas.</p>`;
        return;
    }
    container.innerHTML = auctions.map(auction => `
        <div class="bg-gray-800 p-4 rounded-lg shadow-md">
            <h3 class="font-bold text-lg text-gray-200">${auction.product_name}</h3>
            <p class="text-gray-400 mt-1">Valor Vencido: R$ ${parseFloat(auction.current_bid).toFixed(2).replace('.', ',')}</p>
            <p class="mt-2 font-semibold">Status do Pagamento: <span class="${auction.payment_status === 'Valor liberado para saque' ? 'text-green-500' : 'text-red-500'}">${auction.payment_status}</span></p>
        </div>
    `).join('');
}

// Inicia a aplicação
document.addEventListener('DOMContentLoaded', loadPageContent);

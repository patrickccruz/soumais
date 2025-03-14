/**
 * Script para forçar o recarregamento das folhas de estilo
 * Isso ajuda a garantir que as alterações no CSS sejam aplicadas imediatamente
 */
document.addEventListener('DOMContentLoaded', function() {
    // Força o recarregamento das folhas de estilo
    const stylesheets = document.querySelectorAll('link[rel="stylesheet"]');
    
    stylesheets.forEach(stylesheet => {
        // Adiciona um parâmetro de timestamp para forçar o recarregamento
        const href = stylesheet.getAttribute('href');
        if (href) {
            const timestamp = new Date().getTime();
            const newHref = href.includes('?') 
                ? `${href}&_=${timestamp}` 
                : `${href}?_=${timestamp}`;
            
            stylesheet.setAttribute('href', newHref);
        }
    });
    
    console.log('Folhas de estilo recarregadas com sucesso!');
}); 
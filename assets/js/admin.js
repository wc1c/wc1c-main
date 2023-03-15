document.addEventListener('DOMContentLoaded', function()
{
    if(document.querySelector(".wc1c-toc"))
    {
        tocbot.init({
            tocSelector: '.wc1c-toc',
            contentSelector: '.wc1c-toc-container',
            headingSelector: 'h1, h2, h3, h4, h5',
            hasInnerContainers: true,
            listClass: 'list-group m-0',
            linkClass: 'stretched-link',
            listItemClass: 'list-group-item',
            activeListItemClass: 'active',
            headingsOffset: 55,
            scrollSmoothOffset: -55,
            positionFixedSelector: '.wc1c-sidebar-toc',
            positionFixedClass: 'is-position-fixed position-sticky',
        });
    }

    const wc1cPopoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]')
    const wc1cPopoverList = [...wc1cPopoverTriggerList].map(wc1cPopoverTriggerEl => new bootstrap.Popover(wc1cPopoverTriggerEl))
});
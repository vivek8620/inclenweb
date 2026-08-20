// Dynamic Blog link insertion disabled by request.
/*
(() => {
  const blogUrl = '/blog/';

  function addBlogLinks() {
    const header = document.querySelector('header');
    if (!header) return;

    const desktopNav = header.querySelector('nav.hidden.lg\\:flex');
    if (desktopNav && !desktopNav.querySelector('[data-public-blog-link]')) {
      const link = document.createElement('a');
      link.href = blogUrl;
      link.textContent = 'Blog';
      link.dataset.publicBlogLink = 'true';
      link.className = 'flex items-center text-14 font-semibold font-roboto px-2 xl:px-3 py-2 transition-colors text-gray-700 hover:text-orange-500';
      const contactLink = desktopNav.querySelector('a[href="/contact/"]');
      desktopNav.insertBefore(link, contactLink || null);
    }

    const mobileHome = header.querySelector('a.block[href="/"]');
    const mobileMenu = mobileHome && mobileHome.parentElement;
    if (mobileMenu && !mobileMenu.querySelector('[data-public-blog-link]')) {
      const link = document.createElement('a');
      link.href = blogUrl;
      link.textContent = 'Blog';
      link.dataset.publicBlogLink = 'true';
      link.className = 'block px-3 py-2.5 rounded text-sm font-semibold text-gray-700 hover:bg-gray-50';
      const contactLink = mobileMenu.querySelector('a[href="/contact/"]');
      mobileMenu.insertBefore(link, contactLink || null);
    }
  }

  addBlogLinks();
  new MutationObserver(addBlogLinks).observe(document.documentElement, {
    childList: true,
    subtree: true
  });
})();
*/


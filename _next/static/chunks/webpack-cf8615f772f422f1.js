(()=>{"use strict";var e={},t={};function r(o){var n=t[o];if(void 0!==n)return n.exports;var a=t[o]={exports:{}},i=!0;try{e[o].call(a.exports,a,a.exports,r),i=!1}finally{i&&delete t[o]}return a.exports}r.m=e,(()=>{var e=[];r.O=(t,o,n,a)=>{if(o){a=a||0;for(var i=e.length;i>0&&e[i-1][2]>a;i--)e[i]=e[i-1];e[i]=[o,n,a];return}for(var u=1/0,i=0;i<e.length;i++){for(var[o,n,a]=e[i],l=!0,d=0;d<o.length;d++)(!1&a||u>=a)&&Object.keys(r.O).every(e=>r.O[e](o[d]))?o.splice(d--,1):(l=!1,a<u&&(u=a));if(l){e.splice(i--,1);var c=n();void 0!==c&&(t=c)}}return t}})(),r.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return r.d(t,{a:t}),t},(()=>{var e,t=Object.getPrototypeOf?e=>Object.getPrototypeOf(e):e=>e.__proto__;r.t=function(o,n){if(1&n&&(o=this(o)),8&n||"object"==typeof o&&o&&(4&n&&o.__esModule||16&n&&"function"==typeof o.then))return o;var a=Object.create(null);r.r(a);var i={};e=e||[null,t({}),t([]),t(t)];for(var u=2&n&&o;"object"==typeof u&&!~e.indexOf(u);u=t(u))Object.getOwnPropertyNames(u).forEach(e=>i[e]=()=>o[e]);return i.default=()=>o,r.d(a,i),a}})(),r.d=(e,t)=>{for(var o in t)r.o(t,o)&&!r.o(e,o)&&Object.defineProperty(e,o,{enumerable:!0,get:t[o]})},r.f={},r.e=e=>Promise.all(Object.keys(r.f).reduce((t,o)=>(r.f[o](e,t),t),[])),r.u=e=>"static/chunks/"+(1761===e?"d0deef33":e)+"."+({1646:"9123ee47220ed70b",1761:"c4122c9a4274eaad",5139:"c5e46d26064a85db"})[e]+".js",r.miniCssF=e=>{},r.g=function(){if("object"==typeof globalThis)return globalThis;try{return this||Function("return this")()}catch(e){if("object"==typeof window)return window}}(),r.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),(()=>{var e={},t="_N_E:";r.l=(o,n,a,i)=>{if(e[o])return void e[o].push(n);if(void 0!==a)for(var u,l,d=document.getElementsByTagName("script"),c=0;c<d.length;c++){var f=d[c];if(f.getAttribute("src")==o||f.getAttribute("data-webpack")==t+a){u=f;break}}u||(l=!0,(u=document.createElement("script")).charset="utf-8",u.timeout=120,r.nc&&u.setAttribute("nonce",r.nc),u.setAttribute("data-webpack",t+a),u.src=r.tu(o)),e[o]=[n];var s=(t,r)=>{u.onerror=u.onload=null,clearTimeout(p);var n=e[o];if(delete e[o],u.parentNode&&u.parentNode.removeChild(u),n&&n.forEach(e=>e(r)),t)return t(r)},p=setTimeout(s.bind(null,void 0,{type:"timeout",target:u}),12e4);u.onerror=s.bind(null,u.onerror),u.onload=s.bind(null,u.onload),l&&document.head.appendChild(u)}})(),r.r=e=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},(()=>{var e;r.tt=()=>(void 0===e&&(e={createScriptURL:e=>e},"undefined"!=typeof trustedTypes&&trustedTypes.createPolicy&&(e=trustedTypes.createPolicy("nextjs#bundler",e))),e)})(),r.tu=e=>r.tt().createScriptURL(e),r.p="/_next/",(()=>{var e={8068:0,6135:0,1741:0,8404:0};r.f.j=(t,o)=>{var n=r.o(e,t)?e[t]:void 0;if(0!==n)if(n)o.push(n[2]);else if(/^(1741|6135|8068|8404)$/.test(t))e[t]=0;else{var a=new Promise((r,o)=>n=e[t]=[r,o]);o.push(n[2]=a);var i=r.p+r.u(t),u=Error();r.l(i,o=>{if(r.o(e,t)&&(0!==(n=e[t])&&(e[t]=void 0),n)){var a=o&&("load"===o.type?"missing":o.type),i=o&&o.target&&o.target.src;u.message="Loading chunk "+t+" failed.\n("+a+": "+i+")",u.name="ChunkLoadError",u.type=a,u.request=i,n[1](u)}},"chunk-"+t,t)}},r.O.j=t=>0===e[t];var t=(t,o)=>{var n,a,[i,u,l]=o,d=0;if(i.some(t=>0!==e[t])){for(n in u)r.o(u,n)&&(r.m[n]=u[n]);if(l)var c=l(r)}for(t&&t(o);d<i.length;d++)a=i[d],r.o(e,a)&&e[a]&&e[a][0](),e[a]=0;return r.O(c)},o=self.webpackChunk_N_E=self.webpackChunk_N_E||[];o.forEach(t.bind(null,0)),o.push=t.bind(null,o.push.bind(o))})()})();
;(function() {
    try {
        const cached = localStorage.getItem('inclen_nav_menu_structure');
        if (cached) {
            window.NAV_MENU_STRUCTURE = JSON.parse(cached);
        }
    } catch(e) {}

    const originalC_Template = [
        {
            label: "About",
            href: "/about",
            columns: [
                {
                    heading: "Organization",
                    links: [
                        {label: "Who We Are", href: "/about#who-we-are"},
                        {label: "Mission & Vision", href: "/about#what-we-do"},
                        {label: "Global Presence", href: "/about#presence"},
                        {label: "FCRA & Registration", href: "/fcra"}
                    ]
                },
                {
                    heading: "Leadership",
                    links: [
                        {label: "Board of Trustees", href: "/about#team"},
                        {label: "Our Journey", href: "/about#milestones"},
                        {label: "Academic Collaborators", href: "/about#collaborators"}
                    ]
                }
            ],
            promo: {
                variant: "gray",
                title: "Empowering Global Health",
                description: "Learn how INCLEN is working with global health stakeholders to drive meaningful change.",
                href: "/about",
                ctaLabel: "Read More",
                image: "/images/team_collaboration.png",
                imageAlt: "Team collaboration"
            }
        },
        {
            label: "Our Work",
            href: "#",
            columns: [
                {
                    heading: "Focus Areas",
                    links: [
                        {label: "Area of Work", href: "/area-of-work", desc: "Explore our key focus areas and strategic impact domains."},
                        {label: "Research Projects", href: "/research", desc: "Cutting-edge health research, synthesis and analysis."}
                    ]
                },
                {
                    heading: "Key Initiatives",
                    links: [
                        {label: "Somarth Sites", href: "/somarth-sites", desc: "Demographic Development & Environmental Surveillance."},
                        {label: "Capacity Building", href: "/capacity-building", desc: "Strengthening healthcare systems and leadership capabilities."}
                    ]
                },
                {
                    heading: "Outreach",
                    links: [
                        {label: "Engagement & Advocacy", href: "/engagement-advocacy", desc: "Policy advocacy and multi-stakeholder engagement."},
                        {label: "Community Activities", href: "/community-activities", desc: "Community activities and engagement."}
                    ]
                }
            ]
        },
        {
            label: "Our Impact",
            href: "/our-impact",
            columns: [
                {
                    heading: "Overview",
                    links: [
                        {label: "Impact Summary", href: "/our-impact"},
                        {label: "Partners", href: "/partners"}
                    ]
                },
                {
                    heading: "Achievements",
                    links: [
                        {label: "Key Research Findings", href: "/key-research-findings"},
                        {label: "Device Products", href: "/device-products"},
                        {label: "Policy Influence", href: "/policy-influence"}
                    ]
                }
            ],
            promo: {
                variant: "brand",
                title: "Transforming Lives",
                description: "See how our research translates into real-world health solutions.",
                href: "/our-impact",
                ctaLabel: "Explore Impact",
                image: "https://images.pexels.com/photos/6120214/pexels-photo-6120214.jpeg",
                imageAlt: "Health impact"
            }
        },
        {
            label: "Careers",
            href: "/careers",
            columns: [
                {
                    heading: "Opportunities",
                    links: [
                        {label: "Current Openings", href: "/careers"},
                        {label: "Fellowships", href: "/fellowship"},
                        {label: "Internships", href: "/internships"}
                    ]
                }
            ],
            heroBanner: {
                image: "/images/hero_village_data.png",
                title: "Shape the Future of Health",
                description: "Join a team of dedicated professionals working towards global health equity.",
                ctaLabel: "View All Openings",
                ctaHref: "/careers"
            }
        },
        {
            label: "Get Involved",
            href: "#",
            columns: [
                {
                    heading: "Academic & Research Calls",
                    links: [
                        {label: "Academic Association", href: "/academic-association", desc: "Join our global network of academic professionals and researchers."},
                        {label: "Research Partnership", href: "/partnership", desc: "Collaborate with INCLEN on groundbreaking health studies."}
                    ]
                },
                {
                    heading: "Strategic Partnerships",
                    links: [
                        {label: "Industry Partnership", href: "/industry-partnership", desc: "Strategic alliances for healthcare innovation and delivery."}
                    ]
                }
            ]
        },
        {
            label: "Insights",
            href: "#",
            columns: [
                {
                    heading: "Updates",
                    links: [
                        {label: "News", href: "/news", desc: "Latest updates and press releases from INCLEN."},
                        {label: "Events", href: "/events", desc: "Upcoming conferences, workshops, and webinars."}
                    ]
                },
                {
                    heading: "Media",
                    links: [
                        {label: "Announcements", href: "/announcements", desc: "Official notifications and public notices."},
                        {label: "Headlines", href: "/headlines", desc: "INCLEN in the news and media features."}
                    ]
                }
            ]
        },
        {
            label: "Resources",
            href: "#",
            columns: [
                {
                    heading: "Publications",
                    links: [
                        {label: "All Publications", href: "/publications"},
                        {label: "Annual Reports", href: "/annual-reports"},
                        {label: "Newsletters", href: "/newsletters"}
                    ]
                },
                {
                    heading: "Tools & Data",
                    links: [
                        {label: "Data Repository", href: "/data-repository"},
                        {label: "Research Tools", href: "/research-tools"},
                        {label: "Training Materials", href: "/training-materials"}
                    ]
                }
            ],
            imageCard: {
                image: "/images/publication_hero.jpg",
                title: "Knowledge Hub",
                description: "Access our extensive library of research and resources."
            }
        }
    ];

    function buildReactMenu(dbStructure) {
        return dbStructure.map(dbParent => {
            const origParent = originalC_Template.find(p => p.label.toLowerCase() === dbParent.label.toLowerCase() || p.href.includes(dbParent.key));
            
            if (!origParent) {
                // Newly created parent
                return {
                    label: dbParent.label,
                    href: dbParent.href || "#",
                    columns: dbParent.children ? [{
                        heading: "Links",
                        links: dbParent.children.map(c => ({ label: c.label, href: c.href || "#" }))
                    }] : []
                };
            }

            const rebuiltColumns = [];
            if (origParent.columns && dbParent.children) {
                origParent.columns.forEach(origCol => {
                    const newLinks = [];
                    origCol.links.forEach(origLink => {
                        const dbChild = dbParent.children.find(c => c.label.toLowerCase() === origLink.label.toLowerCase());
                        if (dbChild && dbChild.visible) {
                            newLinks.push({
                                label: dbChild.label,
                                href: (dbChild.href && dbChild.href !== '#') ? dbChild.href : origLink.href,
                                desc: origLink.desc
                            });
                        }
                    });
                    if (newLinks.length > 0) {
                        rebuiltColumns.push({
                            heading: origCol.heading,
                            links: newLinks
                        });
                    }
                });

                // Find any newly created children under this parent
                const allOriginalLinkLabels = origParent.columns.flatMap(col => col.links.map(l => l.label.toLowerCase()));
                const newChildren = dbParent.children.filter(c => c.visible && !allOriginalLinkLabels.includes(c.label.toLowerCase()));
                if (newChildren.length > 0) {
                    if (rebuiltColumns.length > 0) {
                        rebuiltColumns[0].links.push(...newChildren.map(c => ({ label: c.label, href: c.href || "#" })));
                    } else {
                        rebuiltColumns.push({
                            heading: "Links",
                            links: newChildren.map(c => ({ label: c.label, href: c.href || "#" }))
                        });
                    }
                }
            }

            return {
                ...origParent,
                label: dbParent.label,
                visible: dbParent.visible,
                columns: rebuiltColumns
            };
        }).filter(p => p.visible);
    }

    let visibilityData = null;

    function fetchVisibility() {
        const hostname = window.location.hostname;
        const isLocal = hostname === 'localhost' || hostname === '127.0.0.1' || hostname === '[::1]' || hostname.startsWith('192.168.');
        
        const localOrigin = 'http://localhost/inclenweb';
        const prodOrigin = 'https://inclentrust.org';
        
        const origin = isLocal ? localOrigin : window.location.origin;
        
        const primaryUrl = origin + '/admin/admin/wp-json/navigation/v1/all';
        const fallbackUrl = origin + '/admin/admin/index.php?rest_route=/navigation/v1/all';
        const prodFallbackUrl = prodOrigin + '/admin/admin/index.php?rest_route=/navigation/v1/all';

        fetch(primaryUrl)
            .then(res => {
                if (!res.ok) throw new Error("Primary failed");
                return res.json();
            })
            .then(data => handleData(data))
            .catch(err => {
                fetch(fallbackUrl)
                    .then(res => res.json())
                    .then(data => handleData(data))
                    .catch(err2 => {
                        if (isLocal) {
                            fetch(prodFallbackUrl)
                                .then(res => res.json())
                                .then(data => handleData(data))
                                .catch(err3 => console.error("[INCLEN Nav API] Production fallback fetch failed:", err3));
                        } else {
                            console.error("[INCLEN Nav API] Fetch failed:", err2);
                        }
                    });
            });
    }

    function handleData(data) {
        if (data && data.menu_structure) {
            try {
                const rebuiltC = buildReactMenu(data.menu_structure);
                const rebuiltCString = JSON.stringify(rebuiltC);
                const currentCString = JSON.stringify(window.NAV_MENU_STRUCTURE);
                
                localStorage.setItem('inclen_nav_menu_structure', rebuiltCString);
                
                if (currentCString !== rebuiltCString) {
                    window.NAV_MENU_STRUCTURE = rebuiltC;
                    if (!window.location.href.includes('wp-admin')) {
                        window.location.reload();
                    }
                }
            } catch(e) {
                console.error("[INCLEN Nav] Rebuild failed:", e);
            }

            visibilityData = parseMenuStructure(data.menu_structure);
            applyVisibility();
        }
    }

    function parseMenuStructure(structure) {
        const map = {};
        structure.forEach(item => {
            map[item.key] = {
                visible: item.visible,
                label: item.label,
                isParent: true
            };
            if (item.children) {
                item.children.forEach(child => {
                    map[child.key] = {
                        visible: child.visible,
                        label: child.label,
                        isParent: false,
                        parentKey: item.key
                    };
                });
            }
        });
        return map;
    }

    function applyVisibility() {
        if (!visibilityData) return;

        const keySearchMap = {
            'about': ['about'],
            'about_us': ['about', 'about us'],
            'our_work': ['our work'],
            'research': ['our work', 'research'],
            'our_impact': ['our impact'],
            'impact': ['our impact', 'impact'],
            'careers': ['careers'],
            'get_involved': ['get involved'],
            'network': ['get involved', 'network'],
            'insights': ['insights'],
            'news_events': ['insights', 'news & events', 'news \u0026 events'],
            'resources': ['resources'],
            'publications': ['resources', 'publications'],
            'contact': ['contact', 'contact us'],

            // About children
            'about_who_we_are': ['who we are'],
            'about_mission_vision': ['mission & vision', 'mission \u0026 vision', 'vision & mission', 'vision \u0026 mission'],
            'about_vision_mission': ['mission & vision', 'mission \u0026 vision', 'vision & mission', 'vision \u0026 mission'],
            'about_presence': ['global presence'],
            'about_fcra': ['fcra & registration', 'fcra \u0026 registration'],
            'about_board': ['board of trustees'],
            'about_journey': ['our journey'],
            'about_collaborators': ['academic collaborators'],

            // Our Work / Research children
            'work_area': ['area of work'],
            'research_areas': ['area of work', 'research areas', 'key research areas'],
            'work_research': ['research projects'],
            'research_ongoing_projects': ['research projects', 'ongoing projects'],
            'work_somaarth': ['somarth sites', 'somaarth sites'],
            'research_somaarth': ['somarth sites', 'somaarth sites', 'somaarth ddess'],
            'work_capacity': ['capacity building'],
            'work_engagement': ['engagement & advocacy', 'engagement \u0026 advocacy'],
            'work_community': ['community activities'],
            'research_data_analytics': ['data & analytics', 'data \u0026 analytics', 'research intelligence'],
            'research_funding': ['funding opportunities'],

            // Our Impact children
            'impact_summary': ['impact summary'],
            'impact_partners': ['partners'],
            'impact_findings': ['key research findings'],
            'impact_device_products': ['device products'],
            'impact_policy_influence': ['policy influence'],

            // Careers children
            'careers_openings': ['current openings'],
            'careers_fellowships': ['fellowships'],
            'careers_internships': ['internships'],

            // Get Involved / Network children
            'involved_academic': ['academic association'],
            'involved_research': ['research partnership'],
            'involved_industry': ['industry partnership'],
            'network_global': ['global network'],
            'network_regional': ['regional networks'],
            'network_indiaclen': ['indiaclen'],
            'network_ipen': ['ipen'],

            // Insights children
            'insights_news': ['news'],
            'insights_events': ['events'],
            'insights_announcements': ['announcements'],
            'insights_headlines': ['headlines'],

            // Resources / Publications children
            'resources_all': ['all publications'],
            'publications_papers': ['all publications', 'research papers'],
            'resources_reports': ['annual reports'],
            'publications_reports': ['annual reports', 'reports'],
            'resources_newsletters': ['newsletters'],
            'publications_newsletters': ['newsletters'],
            'publications_policy_briefs': ['policy briefs'],
            'resources_repository': ['data repository'],
            'resources_tools': ['research tools'],
            'resources_training': ['training materials'],

            // Partners children
            'partners_collaborators': ['collaborators'],
            'partners_funders': ['funders']
        };

        Object.keys(visibilityData).forEach(key => {
            const config = visibilityData[key];
            const searchTerms = keySearchMap[key] || [config.label.toLowerCase()];
            
            if (config.visible === false) {
                if (config.isParent) {
                    hideTopLevel(searchTerms);
                } else {
                    hideSubmenuLink(searchTerms);
                }
            } else {
                if (config.isParent) {
                    showTopLevel(searchTerms);
                } else {
                    showSubmenuLink(searchTerms);
                }
            }
        });
    }

    function hideTopLevel(searchTerms) {
        // Desktop
        const desktopNav = document.querySelector('header nav');
        if (desktopNav) {
            const children = desktopNav.children;
            for (let i = 0; i < children.length; i++) {
                const child = children[i];
                const linkOrButton = child.tagName === 'A' || child.tagName === 'BUTTON' ? child : child.querySelector('a, button');
                if (linkOrButton) {
                    const labelText = linkOrButton.textContent.trim().toLowerCase();
                    if (searchTerms.includes(labelText)) {
                        child.style.setProperty('display', 'none', 'important');
                    }
                }
            }
        }

        // Mobile
        const mobileContainer = document.querySelector('header .lg\\:hidden.fixed.right-0 div.px-3.py-3') || 
                                document.querySelector('header .lg\\:hidden.fixed.right-0 div.px-3');
        if (mobileContainer) {
            const children = mobileContainer.children;
            for (let i = 0; i < children.length; i++) {
                const child = children[i];
                const linkOrButton = child.tagName === 'A' || child.tagName === 'BUTTON' ? child : child.querySelector('a, button');
                if (linkOrButton) {
                    const labelText = linkOrButton.textContent.trim().toLowerCase();
                    if (searchTerms.includes(labelText)) {
                        child.style.setProperty('display', 'none', 'important');
                    }
                }
            }
        }
    }

    function showTopLevel(searchTerms) {
        // Desktop
        const desktopNav = document.querySelector('header nav');
        if (desktopNav) {
            const children = desktopNav.children;
            for (let i = 0; i < children.length; i++) {
                const child = children[i];
                const linkOrButton = child.tagName === 'A' || child.tagName === 'BUTTON' ? child : child.querySelector('a, button');
                if (linkOrButton) {
                    const labelText = linkOrButton.textContent.trim().toLowerCase();
                    if (searchTerms.includes(labelText)) {
                        if (child.style.display === 'none') {
                            child.style.removeProperty('display');
                        }
                    }
                }
            }
        }

        // Mobile
        const mobileContainer = document.querySelector('header .lg\\:hidden.fixed.right-0 div.px-3.py-3') || 
                                document.querySelector('header .lg\\:hidden.fixed.right-0 div.px-3');
        if (mobileContainer) {
            const children = mobileContainer.children;
            for (let i = 0; i < children.length; i++) {
                const child = children[i];
                const linkOrButton = child.tagName === 'A' || child.tagName === 'BUTTON' ? child : child.querySelector('a, button');
                if (linkOrButton) {
                    const labelText = linkOrButton.textContent.trim().toLowerCase();
                    if (searchTerms.includes(labelText)) {
                        if (child.style.display === 'none') {
                            child.style.removeProperty('display');
                        }
                    }
                }
            }
        }
    }

    function hideSubmenuLink(searchTerms) {
        // Desktop mega menu
        const megaMenu = document.querySelector('header div.bg-white.shadow-2xl');
        if (megaMenu) {
            const links = megaMenu.querySelectorAll('a');
            links.forEach(link => {
                const labelSpan = link.querySelector('span');
                const text = (labelSpan ? labelSpan.textContent : link.textContent).trim().toLowerCase();
                if (searchTerms.includes(text)) {
                    link.style.setProperty('display', 'none', 'important');
                }
            });
        }

        // Mobile sub-menu
        const mobileNav = document.querySelector('header .lg\\:hidden.fixed.right-0');
        if (mobileNav) {
            const links = mobileNav.querySelectorAll('a');
            links.forEach(link => {
                const labelSpan = link.querySelector('span');
                const text = (labelSpan ? labelSpan.textContent : link.textContent).trim().toLowerCase();
                if (searchTerms.includes(text)) {
                    link.style.setProperty('display', 'none', 'important');
                }
            });
        }
    }

    function showSubmenuLink(searchTerms) {
        // Desktop mega menu
        const megaMenu = document.querySelector('header div.bg-white.shadow-2xl');
        if (megaMenu) {
            const links = megaMenu.querySelectorAll('a');
            links.forEach(link => {
                const labelSpan = link.querySelector('span');
                const text = (labelSpan ? labelSpan.textContent : link.textContent).trim().toLowerCase();
                if (searchTerms.includes(text)) {
                    if (link.style.display === 'none') {
                        link.style.removeProperty('display');
                    }
                }
            });
        }

        // Mobile sub-menu
        const mobileNav = document.querySelector('header .lg\\:hidden.fixed.right-0');
        if (mobileNav) {
            const links = mobileNav.querySelectorAll('a');
            links.forEach(link => {
                const labelSpan = link.querySelector('span');
                const text = (labelSpan ? labelSpan.textContent : link.textContent).trim().toLowerCase();
                if (searchTerms.includes(text)) {
                    if (link.style.display === 'none') {
                        link.style.removeProperty('display');
                    }
                }
            });
        }
    }

    if (typeof window !== 'undefined') {
        fetchVisibility();
        // Periodically apply visibility dynamically for the client-side SPA transitions
        setInterval(applyVisibility, 150);
    }
})();
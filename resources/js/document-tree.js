import Alpine from 'alpinejs';

document.addEventListener('alpine:init', () => {
    Alpine.data('documentTree', (apiUrl) => ({
        apiUrl: apiUrl,
        categories: [],
        filteredCategories: [],
        openCategories: [],
        openSubs: [],
        search: '',
        statusFilter: 'all',
        loading: true,

        get emptyState() {
            return this.readyState && this.filteredCategories.length === 0;
        },

        get readyState() {
            return !this.loading;
        },

        init() {
            fetch(this.apiUrl)
                .then(r => r.json())
                .then(data => {
                    this.categories = data;
                    this.applyFilter();
                    this.loading = false;
                    if (data.length > 0) {
                        this.openCategories.push(data[0].id);
                    }
                })
                .catch(e => {
                    console.error(e);
                    this.loading = false;
                });
        },

        isCategoryOpen(id) {
            return this.openCategories.includes(id);
        },

        isSubOpen(id) {
            return this.openSubs.includes(id);
        },

        toggleCategory(id) {
            if (this.openCategories.includes(id)) {
                this.openCategories = this.openCategories.filter(i => i !== id);
            } else {
                this.openCategories.push(id);
            }
        },

        toggleSub(id) {
            if (this.openSubs.includes(id)) {
                this.openSubs = this.openSubs.filter(i => i !== id);
            } else {
                this.openSubs.push(id);
            }
        },

        countDocuments(category) {
            return category.subCategories.reduce((sum, sub) => {
                return sum + sub.subjects.reduce((s, subject) => s + subject.documents.length, 0);
            }, 0);
        },

        applyFilter() {
            const q = this.search.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            this.filteredCategories = this.categories.map(category => {
                const filteredSubs = category.subCategories.map(sub => {
                    const filteredSubjects = sub.subjects.filter(subject => {
                        const statusOk = this.statusFilter === 'all' || subject.status === this.statusFilter;
                        const subjectText = (subject.title + ' ' + subject.slug).toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                        const docMatch = subject.documents.some(doc => {
                            const t = (doc.title + ' ' + doc.filename + ' ' + doc.category).toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                            return t.includes(q);
                        });
                        return statusOk && (q === '' || subjectText.includes(q) || docMatch);
                    });
                    return filteredSubjects.length > 0 ? { ...sub, subjects: filteredSubjects } : null;
                }).filter(Boolean);
                return filteredSubs.length > 0 ? { ...category, subCategories: filteredSubs } : null;
            }).filter(Boolean);
        }
    }));
});

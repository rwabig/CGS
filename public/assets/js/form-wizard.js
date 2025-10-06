// public/assets/js/form-wizard.js
document.addEventListener("DOMContentLoaded", () => {
  const BASE = '/CGS'; // adjust base path if needed

  async function fetchJSON(url) {
    const res = await fetch(url, { credentials: 'same-origin' });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return await res.json();
  }

  async function postJSON(url, payload) {
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify(payload)
    });
    return await res.json();
  }

  function reset(el, placeholder) {
    if (el) el.innerHTML = `<option value="">${placeholder}</option>`;
  }

  async function saveStep(stepEl) {
    const typeSel = document.querySelector('[name="user_type"]');
    const profileType = typeSel ? typeSel.value : 'student';
    const payload = { profile_type: profileType };

    const inputs = stepEl.querySelectorAll('input[name], select[name], textarea[name]');
    inputs.forEach(i => {
      if (!i.name) return;
      if (i.type === 'checkbox') payload[i.name] = i.checked ? i.value : '';
      else if (i.type !== 'file') payload[i.name] = i.value;
    });

    try {
      const result = await postJSON(BASE + '/api/save_profile_step.php', payload);
      console.log("Autosave:", result);
    } catch (err) {
      console.error("Save error:", err);
    }
  }

  // Load staff titles
  async function loadStaffTitles() {
  try {
    const data = await fetchJSON(BASE + '/api/get_staff_titles.php');
    if (data.status === 'ok') {
      const select = document.querySelector("#staff_title_id");
      if (select) {
        reset(select, "-- Select Title/Post --");
        data.titles.forEach(t => {
          const opt = document.createElement("option");
          opt.value = t.id;
          opt.textContent = t.name;
          select.appendChild(opt);
        });
      }
    }
  } catch (err) {
    console.error("Error loading staff titles:", err);
  }
  }

  // Load Directories + Sections
  async function loadDirectories() {
    try {
      const data = await fetchJSON(BASE + '/api/get_directories.php?include_sections=1');
      if (data.status === 'ok') {
        const dirSelect = document.querySelector("#directory_id");
        const secSelect = document.querySelector("#section_id");

        if (dirSelect) {
          reset(dirSelect, "-- Select Directory --");
          data.directories.forEach(dir => {
            const opt = document.createElement("option");
            opt.value = dir.id;
            opt.textContent = `${dir.organization} - ${dir.name}`;
            dirSelect.appendChild(opt);
          });

          if (secSelect) {
            dirSelect.addEventListener("change", e => {
              reset(secSelect, "-- Select Section --");
              const selected = data.directories.find(d => d.id == e.target.value);
              if (selected && selected.sections) {
                selected.sections.forEach(sec => {
                  const opt = document.createElement("option");
                  opt.value = sec.id;
                  opt.textContent = sec.name;
                  secSelect.appendChild(opt);
                });
              }
              const stepEl = dirSelect.closest('.wizard-step');
              if (stepEl) saveStep(stepEl);
            });
          }
        }
      }
    } catch (err) {
      console.error("Error loading directories:", err);
    }
  }

  // Org → Dept + Category → Program
  function initOrgDeptCatProgram() {
    const org = document.querySelector("#organization_id");
    const dept = document.querySelector("#department_id");
    const cat  = document.querySelector("#category_id");
    const prog = document.querySelector("#program_id");

    async function refreshDepartments() {
      reset(dept, "-- Select Department --");
      reset(prog, "-- Select Program --");
      if (!org.value) return;
      try {
        const data = await fetchJSON(BASE + `/api/get_departments.php?organization_id=${org.value}`);
        if (data.status === 'ok') {
          data.departments.forEach(d => {
            const opt = document.createElement("option");
            opt.value = d.id;
            opt.textContent = d.name;
            dept.appendChild(opt);
          });
        }
      } catch (err) { console.error("Dept load error", err); }
    }

    async function refreshCategories() {
      reset(cat, "-- Select Category --");
      reset(prog, "-- Select Program --");
      if (!org.value) return;
      try {
        const data = await fetchJSON(BASE + `/api/get_categories.php?organization_id=${org.value}`);
        if (data.status === 'ok') {
          data.categories.forEach(c => {
            const opt = document.createElement("option");
            opt.value = c.id;
            opt.textContent = c.name;
            cat.appendChild(opt);
          });
        }
      } catch (err) { console.error("Cat load error", err); }
    }

    async function refreshPrograms() {
      reset(prog, "-- Select Program --");
      if (!dept.value || !cat.value) return;
      try {
        const data = await fetchJSON(BASE + `/api/get_programs.php?department_id=${dept.value}&category_id=${cat.value}`);
        if (data.status === 'ok') {
          data.programs.forEach(p => {
            const opt = document.createElement("option");
            opt.value = p.id;
            opt.textContent = p.code ? `${p.name} (${p.code})` : p.name;
            prog.appendChild(opt);
          });
        }
      } catch (err) { console.error("Program load error", err); }
    }

    if (org) {
      org.addEventListener("change", async () => {
        await refreshDepartments();
        await refreshCategories();
        const stepEl = org.closest('.wizard-step');
        if (stepEl) saveStep(stepEl);
      });
    }

    if (dept) {
      dept.addEventListener("change", async () => {
        await refreshPrograms();
        const stepEl = dept.closest('.wizard-step');
        if (stepEl) saveStep(stepEl);
      });
    }

    if (cat) {
      cat.addEventListener("change", async () => {
        await refreshPrograms();
        const stepEl = cat.closest('.wizard-step');
        if (stepEl) saveStep(stepEl);
      });
    }

    if (prog) {
      prog.addEventListener("change", () => {
        const stepEl = prog.closest('.wizard-step');
        if (stepEl) saveStep(stepEl);
      });
    }
  }

  // Wizard nav
  function initWizard() {
    const steps = Array.from(document.querySelectorAll(".wizard-step"));
    if (!steps.length) return;
    let current = 0;

    function showStep(i) {
      steps.forEach((s, idx) => s.style.display = idx === i ? "block" : "none");
      current = i;
    }

    document.querySelectorAll(".btn-next").forEach(btn => {
      btn.addEventListener("click", async () => {
        const stepEl = steps[current];
        await saveStep(stepEl);
        if (current < steps.length - 1) showStep(current + 1);
      });
    });

    document.querySelectorAll(".btn-prev").forEach(btn => {
      btn.addEventListener("click", () => {
        if (current > 0) showStep(current - 1);
      });
    });

    steps.forEach(step => {
      const debounced = () => saveStep(step);
      step.addEventListener("input", e => { if (e.target.type !== 'file') debounced(); });
      step.addEventListener("change", e => { if (e.target.type !== 'file') debounced(); });
    });

    showStep(0);
  }

  // Init
  loadDirectories();
  initOrgDeptCatProgram();
  loadStaffTitles();   // <--- NEW
  initWizard();
});

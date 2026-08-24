# ⚡ Quick Reference Guide - ZenithSoles Project Rules

## 🚨 **CRITICAL RULES - MUST FOLLOW**

### **Before Any Code Change**
1. ✅ Read architecture documentation
2. ✅ Review CHANGELOG.md
3. ✅ Understand data flow
4. ✅ Plan changes in CHANGELOG.md
5. ✅ Test rollback strategy

### **Security (Non-Negotiable)**
1. 🔒 Sanitize ALL external input
2. 🔒 Store secrets in .env only
3. 🔒 Use hashed passwords
4. 🔒 Protect ALL endpoints with auth
5. 🔒 Apply least-privilege access

### **Testing (Mandatory)**
1. ✅ Every feature needs tests
2. ✅ Run full test suite before merge
3. ✅ Fixes must include regression tests
4. ✅ Test real-world scenarios
5. ✅ Meet the coverage threshold enforced by the current CI/release contract; never invent a percentage from an unverified run

### **Database Changes**
1. 📊 Always use migrations
2. 📊 Test on staging first
3. 📊 Backup before changes
4. 📊 Never delete fields without analysis
5. 📊 Document all changes

---

## 🎯 **AFFILIATE SYSTEM SPECIFIC**

### **Click Tracking**
- ✅ Log all clicks with timestamp
- ✅ Store IP and user agent
- ✅ Implement deduplication
- ✅ Handle attribution correctly

### **Commission Calculations**
- ✅ Calculate accurately
- ✅ Support multiple structures
- ✅ Handle sub-affiliate splits
- ✅ Implement audit trails

### **API Integration**
- ✅ Handle different API formats
- ✅ Implement retry logic
- ✅ Cache responses appropriately
- ✅ Monitor rate limits

---

## 🚀 **RELEASE CHECKLIST**

### **Pre-Release**
- [ ] All tests passing
- [ ] Security audit complete
- [ ] Performance testing done
- [ ] Documentation updated
- [ ] Changelog updated
- [ ] Rollback plan ready

### **Post-Release**
- [ ] Monitoring active
- [ ] Performance metrics tracked
- [ ] User feedback collected
- [ ] Issues documented

---

## 🔧 **TROUBLESHOOTING STEPS**

1. **Understand the problem** - Don't guess
2. **Change one thing at a time**
3. **Test after each change**
4. **Revert if it breaks**
5. **Document everything**

---

## 📊 **QUALITY GATES**

### **Code Quality**
- [ ] Follows Laravel conventions
- [ ] Security measures implemented
- [ ] Input validation added
- [ ] Error handling complete
- [ ] Tests written and passing

### **Performance**
- [ ] Database queries optimized
- [ ] Caching implemented
- [ ] API response objectives are measured and approved for the relevant environment
- [ ] Mobile performance tested

### **Security**
- [ ] Input sanitization complete
- [ ] Authentication implemented
- [ ] Authorization checked
- [ ] Secrets properly stored
- [ ] HTTPS enforced

---

## 🎨 **UI/UX QUICK CHECKS**

- [ ] Mobile responsive
- [ ] Accessibility compliant
- [ ] Consistent design patterns
- [ ] Fast loading times
- [ ] Cross-browser tested

---

## 📈 **SUCCESS METRICS**

### **Technical**
- System uptime objective is measured and approved for the relevant environment
- API response objective is measured and approved for the relevant environment
- Test coverage is reported from a reproducible CI artifact
- Bug-resolution objective is owner-approved and evidence-backed

### **Business**
- Affiliate conversion rate
- Commission accuracy
- User satisfaction target is owner-approved and measured during the pilot
- Revenue and contribution-margin results are measured from reconciled partner data

---

## 🆘 **EMERGENCY CONTACTS**

### **Critical Issues**
- Security vulnerabilities
- Data integrity problems
- Performance degradation
- System outages

### **Escalation Process**
1. Document the issue
2. Assess impact
3. Notify stakeholders
4. Implement fixes
5. Plan long-term solutions

---

## 📚 **QUICK LINKS**

- **Main Rules**: [project-rules.mcd](./project-rules.mcd)
- **Additional Rules**: [additional-rules.mcd](./additional-rules.mcd)
- **Architecture**: [../docs/architecture.md](../docs/architecture.md)
- **Database**: [../docs/architecture.md](../docs/architecture.md) and current migrations under `database/migrations/`
- **Project Status**: [../README.md](../README.md) and [../STAGING_READINESS_REPORT.md](../STAGING_READINESS_REPORT.md)
- **Phase 1 Gate**: [../docs/PHASE1_REMAINING_DECISIONS.md](../docs/PHASE1_REMAINING_DECISIONS.md)

---

*Keep this guide handy for quick reference during development!*

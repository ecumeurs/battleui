import auth from './auth';

/**
 * @spec-link [[api_character_skill_inventory]]
 * @spec-link [[api_skill_template_browse]]
 */
class SkillService {
    async listTemplates() {
        return await auth.get('/skills/templates');
    }

    async getTemplate(id) {
        return await auth.get(`/skills/templates/${id}`);
    }

    async listCharacterSkills(characterId) {
        return await auth.get(`/profile/character/${characterId}/skills`);
    }

    async roll(characterId, grade = 'I') {
        return await auth.post(`/profile/character/${characterId}/skills/roll`, {}, { params: { grade } });
    }

    async equip(characterId, skillId) {
        return await auth.post(`/profile/character/${characterId}/skills/${skillId}/equip`);
    }

    async unequip(characterId, skillId) {
        return await auth.delete(`/profile/character/${characterId}/skills/${skillId}/unequip`);
    }
}

export default new SkillService();

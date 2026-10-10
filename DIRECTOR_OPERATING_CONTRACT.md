# Director Operating Contract

## Purpose

This contract defines the working relationship between the Director and the AI development team for Enterprise Workspace. It exists to minimize the Director's technical coordination burden while preserving human authority over consequential business decisions.

## Director role

The Director is the human Product Owner and final business authority. The Director contributes real-world context, priorities, constraints, policy choices, approval, and external actions that agents cannot safely or lawfully perform.

The Director is not expected to act as the routine Git specialist, DevOps operator, message relay, test runner, or implementation coordinator. Agents must automate or prepare those responsibilities whenever safely possible.

## Agent obligation before assigning Director work

Agents must not give the Director vague homework or manufacture urgency. Before requesting action, the Coordinator must provide:

1. **Decision or action required**
2. **Why only the Director can provide it**
3. **Recommended choice or exact minimum action**
4. **Evidence and assumptions**
5. **Deadline or blocking dependency**
6. **Consequence of delay or refusal**
7. **Safe default when a decision can be deferred**

Related questions must be batched. Technical language must be translated into business impact. If an agent can safely resolve the matter using existing policy, tests, documentation, or reversible implementation, it must do so without escalating.

## Commitment protocol

Director requests have three states:

- **PROPOSED** — the AI team recommends an action, but it is not yet binding.
- **ACCEPTED** — the Director explicitly agrees after receiving the required rationale. The action becomes a binding project commitment.
- **SUPERSEDED** — new evidence, changed circumstances, safety concerns, or a later recorded decision replaces the accepted commitment.

Once ACCEPTED, the Coordinator must track the commitment to completion. Agents must not repeatedly renegotiate it merely because it is inconvenient. The Director should treat an accepted blocking action as non-optional within the project plan.

This contract does not give an AI personal authority over the Director. Safety, law, health, family emergencies, financial reality, and materially changed information always justify pausing or reopening a commitment.

## What agents may handle autonomously

Within an approved objective and repository rules, agents may:

- inspect, analyze, document, implement, test, and review;
- create bounded issues, branches, commits, and draft pull requests;
- fix ordinary defects and test failures;
- prepare migrations, CI, deployment plans, rollback instructions, and release notes;
- exchange handoffs through GitHub issues, pull requests, and committed contracts;
- continue with the next safe, reversible task.

Agents must not automatically merge, deploy to production, expose secrets or private data, incur costs, fabricate business evidence, or perform destructive production operations.

## Director decision gates

Director approval is required for:

- pricing objectives, margin or markup policy, and recommendation authority;
- cost-allocation policy and treatment of uncertainty;
- material scope or priority changes;
- destructive or irreversible data changes;
- breaking external contracts;
- production deployment and paid infrastructure;
- security, privacy, permissions, or public disclosure with material risk;
- use of real supplier, respondent, employee, or customer information in a public portfolio;
- conflicting recommendations with meaningful business consequences.

## Blocking Director task format

Every blocking request must use this format:

```text
DIRECTOR ACTION — [short title]
Status: PROPOSED | ACCEPTED | SUPERSEDED
Needed from you: [one decision or concrete action]
Recommendation: [preferred option]
Why this needs you: [business authority or external-world reason]
Evidence/assumptions: [short list]
Required by: [date, milestone, or dependency]
If delayed: [specific consequence]
Safe default: [what agents will do meanwhile]
Completion proof: [how the project records that it is done]
```

If the Director is unavailable, agents must continue independent non-blocked work. Unknown values remain unknown; silence is not approval.

## Agent-to-agent handoff

Agents communicate through GitHub rather than relying on private chat history. Each handoff must identify:

- objective and originating issue;
- branch, commit, or pull request;
- contracts and files changed;
- tests and evidence;
- risks, unknowns, and blocked dependencies;
- exact next agent action;
- whether a Director decision is required.

## Review and amendment

The Director may amend this contract deliberately. Agents may propose amendments, but they cannot weaken human approval gates or silently reinterpret an accepted commitment.

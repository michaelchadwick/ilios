<?php

declare(strict_types=1);

namespace App\Service;

class Directory
{
    /**
     * Constructor
     */
    public function __construct(protected LdapManager $ldapManager, protected Config $config)
    {
    }

    /**
     * Get directory information for a single user
     * @param  string $campusId
     */
    public function findByCampusId($campusId): ?array
    {
        $ldapCampusIdProperty = $this->config->get('ldap_directory_campus_id_property');

        $filter = "({$ldapCampusIdProperty}={$campusId})";
        $users = $this->ldapManager->search($filter);
        if ($users !== []) {
            return $users[0];
        }

        return null;
    }

    /**
     * Get directory information for a list of users
     */
    public function findByCampusIds(array $campusIds): ?array
    {
        $ldapCampusIdProperty = $this->config->get('ldap_directory_campus_id_property');
        $campusIds = array_unique($campusIds);
        $filterTerms = array_map(fn($campusId) => "({$ldapCampusIdProperty}={$campusId})", $campusIds);

        $users = [];

        //Split into groups of 50 to avoid LDAP query length limits
        foreach (array_chunk($filterTerms, 50) as $terms) {
            $filterTermsString = implode('', $terms);
            $filter = "(|{$filterTermsString})";

            $users = array_merge($users, $this->ldapManager->search($filter));
        }

        if ($users !== []) {
            return $users;
        }

        return null;
    }

    /**
     * Find everyone in the directory matching these terms
     */
    public function find(array $searchTerms): ?array
    {
        $format = $this->getLdapFilterFormat();
        $filterTerms = array_map(
            fn ($term) => sprintf($format, ldap_escape($term, "", LDAP_ESCAPE_FILTER)),
            $searchTerms
        );
        $filterTermsString = implode('', $filterTerms);
        $filter = "(&{$filterTermsString})";
        $users = $this->ldapManager->search($filter);

        if ($users !== []) {
            return $users;
        }

        return null;
    }

    /**
     * Find all users matching LDAP filter
     * @param  string $filter
     */
    public function findByLdapFilter($filter): ?array
    {
        $users = $this->ldapManager->search($filter);
        if ($users !== []) {
            return $users;
        }

        return null;
    }

    /**
     * Build the ldap filter format string
     * Turns each possible filter property into a pattern we can attach to search terms
     */
    protected function getLdapFilterFormat(): string
    {
        $ldapPreferredFirstNameProperty = $this->config->get('ldap_directory_preferred_first_name_property');
        $ldapPreferredLastNameProperty = $this->config->get('ldap_directory_preferred_last_name_property');
        $attributes = [
            'mail',
            $this->config->get('ldap_directory_campus_id_property'),
            $this->config->get('ldap_directory_display_name_property'),
            $this->config->get('ldap_directory_first_name_property') ?? 'givenName',
            $this->config->get('ldap_directory_last_name_property') ?? 'sn',
        ];
        if ($ldapPreferredFirstNameProperty) {
            $attributes[] = $ldapPreferredFirstNameProperty;
        }
        if ($ldapPreferredLastNameProperty) {
            $attributes[] = $ldapPreferredLastNameProperty;
        }
        $filters = array_map(function ($term) {
            return '(' . $term . '=%1$s*)';
        }, $attributes);
        return '(|' . implode('', $filters) . ')';
    }
}
